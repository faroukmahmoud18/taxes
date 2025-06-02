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
        ]);

        $user = Auth::user();
        $annualGrossIncome = (float)$validated['annual_gross_income'];
        $maritalStatus = $validated['marital_status'];
        $isChurchMember = $request->boolean('is_church_member');
        $expenseYear = $validated['expense_period_year'];

        $totalBusinessExpenses = $user->expenses()
            ->where('is_business_expense', true)
            ->whereYear('expense_date', $expenseYear)
            ->sum('amount');
            
        $annualTaxableIncome = $annualGrossIncome - $totalBusinessExpenses;
        $annualTaxableIncome = max(0, $annualTaxableIncome); 

        $results = $this->taxService->calculateEstimatedIncomeTax(
            $annualTaxableIncome,
            $maritalStatus,
            $isChurchMember
        );
        
        $inputsForSession = $validated;
        $inputsForSession['is_church_member'] = $isChurchMember;
        $inputsForSession['annual_gross_income'] = $annualGrossIncome; // ensure this is available for old() or display

        $resultsForSession = $results;
        $resultsForSession['annual_gross_income'] = $annualGrossIncome;
        $resultsForSession['total_business_expenses_calculated'] = $totalBusinessExpenses;
        $resultsForSession['annual_taxable_income_calculated'] = $annualTaxableIncome;
        $resultsForSession['expense_period_year_used'] = $expenseYear;


        // Instead of passing directly to view, redirect with results flashed to session
        // This follows Post/Redirect/Get pattern and avoids issues with form resubmission on refresh
        return redirect()->route('tax-estimation.show')
                         ->with('tax_estimation_results', $resultsForSession)
                         ->with('tax_estimation_inputs', $inputsForSession) // To repopulate form correctly
                         ->withInput($inputsForSession); // For old() helper
    }
}
