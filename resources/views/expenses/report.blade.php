@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Expense Report') }}
    </h2>
@endsection

@section('content')
<div class="container py-4">
    <div class="card mb-4">
        <div class="card-header">{{ __('Generate Report') }}</div>
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.report') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="period" class="form-label">{{ __('Select Period') }}:</label>
                    <select name="period" id="period" class="form-select" onchange="document.getElementById('customDateFields').style.display = (this.value === 'custom' ? 'flex' : 'none'); if (this.value !== 'custom') this.form.submit();">
                        <option value="current_month" @if($period === 'current_month') selected @endif>{{ __('Current Month') }}</option>
                        <option value="last_month" @if($period === 'last_month') selected @endif>{{ __('Last Month') }}</option>
                        <option value="current_year" @if($period === 'current_year') selected @endif>{{ __('Current Year') }}</option>
                        <option value="all_time" @if($period === 'all_time') selected @endif>{{ __('All Time') }}</option>
                        <option value="custom" @if($period === 'custom') selected @endif>{{ __('Custom Range') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div id="customDateFields" class="row g-3" style="display: {{ $period === 'custom' ? 'flex' : 'none' }};">
                        <div class="col-md-6">
                            <label for="custom_start_date" class="form-label">{{ __('Start Date') }}:</label>
                            <input type="date" name="custom_start_date" id="custom_start_date" class="form-control" value="{{ $customStartDateInput }}">
                        </div>
                        <div class="col-md-6">
                            <label for="custom_end_date" class="form-label">{{ __('End Date') }}:</label>
                            <input type="date" name="custom_end_date" id="custom_end_date" class="form-control" value="{{ $customEndDateInput }}">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">{{ __('Generate Report') }}</button>
                </div>
                 @if($period === 'custom' && (!$customStartDateInput || !$customEndDateInput) && request()->has('custom_start_date'))
                    <div class="col-12 text-danger mt-2"><small>{{ __('For Custom Range, please select both start and end dates.') }}</small></div>
                @endif
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            {{ __('Summary for') }}: <strong class="text-capitalize">{{ str_replace('_', ' ', $period) }}</strong>
            @if($period === 'custom' && $customStartDateInput && $customEndDateInput)
                ({{ \Illuminate\Support\Carbon::parse($customStartDateInput)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($customEndDateInput)->format('M d, Y') }})
            @endif
        </div>
        <div class="card-body">
            <p><strong>{{ __('Total Expenses') }}:</strong> €{{ number_format($totalExpenses, 2, ',', '.') }}</p>
            <p><strong>{{ __('Total Business Expenses') }}:</strong> €{{ number_format($businessExpensesTotal, 2, ',', '.') }}</p>
            <p><strong>{{ __('Total Private Expenses') }}:</strong> €{{ number_format($privateExpensesTotal, 2, ',', '.') }}</p>
        </div>
    </div>

    @if($expensesByCategory->count() > 0)
    <div class="card mb-4">
        <div class="card-header">{{ __('Expenses by Category') }}</div>
        <ul class="list-group list-group-flush">
            @foreach ($expensesByCategory as $category => $amount)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    {{ $category ?: __('Uncategorized') }}
                    <span class="badge bg-primary rounded-pill">€{{ number_format($amount, 2, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    
    @if(isset($expensesForPeriod) && $expensesForPeriod->count() > 0)
    <div class="card">
        <div class="card-header">{{ __('Detailed Expenses for Period') }}</div>
        <div class="card-body">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>{{__('Date')}}</th>
                        <th>{{__('Description')}}</th>
                        <th>{{__('Category')}}</th>
                        <th class="text-end">{{__('Amount')}} (€)</th>
                        <th>{{__('Business?')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expensesForPeriod as $expense)
                    <tr>
                        <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                        <td>{{ $expense->description }}</td>
                        <td>{{ $expense->category ?? '-' }}</td>
                        <td class="text-end">{{ number_format($expense->amount, 2, ',', '.') }}</td>
                        <td>@if($expense->is_business_expense) {{__('Yes')}} @else {{__('No')}} @endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @elseif($period !== 'all_time' || ($customStartDateInput && $customEndDateInput) ) 
    <div class="alert alert-info">
        {{ __('No expenses found for the selected period.') }}
    </div>
    @endif
    
    <div class="mt-4">
        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">{{ __('Back to Expenses List') }}</a>
    </div>
</div>
@endsection
