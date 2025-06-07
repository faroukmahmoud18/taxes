<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TaxService; // Our new service
use App\Models\Expense; // To fetch user's business expenses
use Illuminate\Support\Carbon; // For date calculations if needed for expenses

class TaxEstimationController extends Controller
{
    protected $taxService;

    public function __construct(TaxService $taxService)
    {
        $this->middleware('auth');
        $this->taxService = $taxService;
    }

    /**
     * Show the tax estimation form.
     */
    public function showForm(Request $request)
    {
        $currentYear = now()->year;
        $user = Auth::user();
        $totalBusinessExpenses = $user->expenses()
            ->where('is_business_expense', true)
            ->whereYear('expense_date', $request->input('expense_period_year', $currentYear))
            ->sum('amount');

        return view('tax-estimation.form', [
            'totalBusinessExpenses' => $totalBusinessExpenses,
            'currentYear' => $request->input('expense_period_year', $currentYear), // Use selected year or current
            'results' => session('tax_estimation_results'), // Get results from session if redirected
            'inputs' => $request->old() ?: (session('tax_estimation_inputs') ?: []) // Keep old inputs
        ]);
    }

    /**
     * Calculate and display the estimated tax.
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'annual_gross_income' => 'required|numeric|min:0|max:999999999.99', // Increased max
            'marital_status' => 'required|in:single,married_joint',
            'is_church_member' => 'sometimes|boolean',
            'expense_period_year' => 'required|digits:4|integer|min:2000|max:'.(now()->year + 5), // Allow few future years
            'state_abbreviation' => 'nullable|string|alpha|size:2', // Added state_abbreviation
        ]);

        $user = Auth::user();
        $annualGrossIncome = (float)$validated['annual_gross_income'];
        $maritalStatus = $validated['marital_status'];
        $isChurchMember = $request->boolean('is_church_member'); // boolean helper correctly gets value even if not in $validated
        $expenseYear = $validated['expense_period_year'];
        $stateAbbreviation = $validated['state_abbreviation'] ?? null;

        $totalBusinessExpenses = $user->expenses()
            ->where('is_business_expense', true)
            ->whereYear('expense_date', $expenseYear)
            ->sum('amount');
            
        // Note: The TaxService calculates its own taxableIncome (zve) based on gross income and business expenses.
        // The $annualTaxableIncome variable here is just for intermediate clarity if needed, but not directly passed if service recalculates.
        // However, TaxService expects annualGrossIncome and totalBusinessExpenses separately.

        $serviceResults = $this->taxService->calculateGermanTax(
            $annualGrossIncome,
            $totalBusinessExpenses,
            $isChurchMember,
            $maritalStatus,
            $stateAbbreviation // Pass the new variable
        );
        
        $inputsForSession = $validated; // $validated already contains state_abbreviation if provided
        $inputsForSession['is_church_member'] = $isChurchMember; // Ensure this is correctly captured from the request
        // $inputsForSession['annual_gross_income'] is already in $validated as a string, which is fine for old()

        // The $serviceResults now contains the full structure including its own 'inputs' and 'calculations' keys.
        // We flash the entire $serviceResults.

        return redirect()->route('tax-estimation.show')
                         ->with('tax_estimation_results', $serviceResults) // Pass the whole service result
                         ->with('tax_estimation_inputs', $inputsForSession) // To repopulate form correctly
                         ->withInput($inputsForSession); // For old() helper, ensures $validated fields are available
    }
}
