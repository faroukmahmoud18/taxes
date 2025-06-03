<?php

namespace App\Http\Controllers;

// Deduplicated use statements
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Str; 

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // REPORT_METHOD_ADDED_MARKER_V2
    public function report(\Illuminate\Http\Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $period = $request->input('period', 'current_month'); 
        $startDate = null;
        $endDate = \Illuminate\Support\Carbon::now()->endOfDay();

        $this->prepareReportDates($request, $startDate, $endDate, $period);

        $expensesQuery = $user->expenses();
        if ($startDate) {
            $expensesQuery->whereBetween('expense_date', [$startDate, $endDate]);
        } elseif ($period !== 'all_time') { 
             $startDate = \Illuminate\Support\Carbon::now()->startOfMonth();
             $endDate = \Illuminate\Support\Carbon::now()->endOfMonth();
             $expensesQuery->whereBetween('expense_date', [$startDate, $endDate]);
             $period = 'current_month';
        }
        
        $expensesForPeriod = $expensesQuery->orderBy('expense_date', 'desc')->get();

        $totalExpenses = $expensesForPeriod->sum('amount');
        
        $expensesByCategory = $expensesForPeriod->groupBy('category')
            ->map(function ($group) {
                return $group->sum('amount');
            })->sortDesc();

        $businessExpensesTotal = $expensesForPeriod->where('is_business_expense', true)->sum('amount');
        $privateExpensesTotal = $expensesForPeriod->where('is_business_expense', false)->sum('amount');
        
        $customStartDateInput = $request->input('custom_start_date', $startDate ? $startDate->format('Y-m-d') : '');
        $customEndDateInput = $request->input('custom_end_date', $endDate ? $endDate->format('Y-m-d') : '');

        return view('expenses.report', compact(
            'expensesForPeriod',
            'totalExpenses',
            'expensesByCategory',
            'businessExpensesTotal',
            'privateExpensesTotal',
            'period',
            'customStartDateInput',
            'customEndDateInput'
        ));
    }
    
    private function prepareReportDates(\Illuminate\Http\Request $request, &$startDate, &$endDate, &$period)
    {
        if($request->filled('custom_start_date') && $request->filled('custom_end_date')) {
            try {
                $customStart = \Illuminate\Support\Carbon::parse($request->input('custom_start_date'))->startOfDay();
                $customEnd = \Illuminate\Support\Carbon::parse($request->input('custom_end_date'))->endOfDay();

                if ($customStart->isValid() && $customEnd->isValid() && $customStart->lte($customEnd)) {
                    $startDate = $customStart;
                    $endDate = $customEnd;
                    $period = 'custom';
                    return;
                }
            } catch (\Exception $e) {
                 \Illuminate\Support\Facades\Log::warning('Invalid custom date format for expense report: ' . $e->getMessage());
            }
        }

        switch ($period) {
            case 'current_month':
                $startDate = \Illuminate\Support\Carbon::now()->startOfMonth();
                $endDate = \Illuminate\Support\Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = \Illuminate\Support\Carbon::now()->subMonthNoOverflow()->startOfMonth();
                $endDate = \Illuminate\Support\Carbon::now()->subMonthNoOverflow()->endOfMonth();
                break;
            case 'current_year':
                $startDate = \Illuminate\Support\Carbon::now()->startOfYear();
                $endDate = \Illuminate\Support\Carbon::now()->endOfYear();
                break;
            case 'all_time':
                $startDate = null; 
                $endDate = \Illuminate\Support\Carbon::now()->endOfDay();
                // $period is already 'all_time'
                break;
            default: // Handles 'custom' with invalid dates, or any other invalid period string
                $startDate = \Illuminate\Support\Carbon::now()->startOfMonth();
                $endDate = \Illuminate\Support\Carbon::now()->endOfMonth();
                $period = 'current_month';
                break;
        }
    }
    // Removed duplicated/malformed code block that was here.

    public function index()
    {
        $expenses = Auth::user()->expenses()->latest('expense_date')->paginate(10);
        return view('expenses.index', compact('expenses'));
    }

    public function create()
    {
        return view('expenses.create');
    }

    public function store(StoreExpenseRequest $request)
    {
        $validatedData = $request->validated();
        
        $expense = new Expense();
        $expense->user_id = Auth::id();
        $expense->amount = $validatedData['amount'];
        $expense->expense_date = $validatedData['expense_date'];
        $expense->category = $validatedData['category'] ?? null;
        $expense->is_business_expense = $validatedData['is_business_expense'] ?? false; // From prepared data

        // Handle translatable description
        // Ensure at least one description was provided (FormRequest should handle this)
        foreach (['en', 'de', 'ar'] as $locale) {
            if (!empty($validatedData['description'][$locale])) {
                $expense->setTranslation('description', $locale, $validatedData['description'][$locale]);
            }
        }
        
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('receipts/' . Auth::id(), $filename, 'public'); // Store in public/storage/receipts
            $expense->receipt_path = $path; 
        }
        
        $expense->save();
        return redirect()->route('expenses.index')->with('success', __('Expense recorded successfully.'));
    }

    public function show(Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        // Typically, show redirects to edit for expenses or is part of a report.
        return view('expenses.edit', compact('expense')); 
    }

    public function edit(Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        return view('expenses.edit', compact('expense'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validatedData = $request->validated();
        $expense->amount = $validatedData['amount'];
        $expense->expense_date = $validatedData['expense_date'];
        $expense->category = $validatedData['category'] ?? null;
        $expense->is_business_expense = $validatedData['is_business_expense'] ?? false; // From prepared data

        $expense->forgetAllTranslations('description');
        foreach (['en', 'de', 'ar'] as $locale) {
            if (!empty($validatedData['description'][$locale])) {
                $expense->setTranslation('description', $locale, $validatedData['description'][$locale]);
            }
        }

        if ($request->hasFile('receipt')) {
            if ($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $file = $request->file('receipt');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('receipts/' . Auth::id(), $filename, 'public');
            $expense->receipt_path = $path;
        } elseif ($request->boolean('remove_receipt')) { 
             if ($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path)) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $expense->receipt_path = null;
        }
        
        $expense->save();
        return redirect()->route('expenses.index')->with('success', __('Expense updated successfully.'));
    }

    public function destroy(Expense $expense)
    {
        if ($expense->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
        if ($expense->receipt_path && Storage::disk('public')->exists($expense->receipt_path)) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', __('Expense deleted successfully.'));
    }
}
