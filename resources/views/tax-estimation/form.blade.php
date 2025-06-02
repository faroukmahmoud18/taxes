@extends('layouts.app')

@section('title', __('Tax Estimation Calculator'))

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Tax Estimation Calculator') }}
    </h2>
@endsection

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <h1 class="mb-4 text-center">{{ __('Tax Estimation Calculator') }} ({{ __('Germany') }} {{ $currentYear }})</h1>

            <div class="alert alert-warning" role="alert">
                <strong>{{ __('Disclaimer:') }}</strong> {{ __('The calculations provided are estimates for informational purposes only and do not constitute professional tax advice. Tax laws are complex and subject to change. Consult with a qualified tax advisor for precise calculations and professional advice tailored to your situation.') }}
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">{{ __('Enter Your Details') }}</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tax-estimation.calculate') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="annual_gross_income" class="form-label">{{ __('Your Estimated Annual Gross Income (€)') }}</label>
                                <input type="number" step="0.01" class="form-control @error('annual_gross_income') is-invalid @enderror" id="annual_gross_income" name="annual_gross_income" value="{{ old('annual_gross_income', $inputs['annual_gross_income'] ?? '') }}" required>
                                @error('annual_gross_income')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expense_period_year" class="form-label">{{ __('Year for Business Expense Calculation') }}</label>
                                <input type="number" class="form-control @error('expense_period_year') is-invalid @enderror" id="expense_period_year" name="expense_period_year" value="{{ old('expense_period_year', $inputs['expense_period_year'] ?? $currentYear) }}" required placeholder="YYYY" min="2000" max="{{ date('Y') + 5 }}">
                                 @error('expense_period_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('Total business expenses for the selected year (€') }} {{ number_format($totalBusinessExpenses, 2, ',', '.') }}) {{ __('will be deducted.') }}</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="marital_status" class="form-label">{{ __('Marital Status') }}</label>
                                <select class="form-select @error('marital_status') is-invalid @enderror" id="marital_status" name="marital_status" required>
                                    <option value="single" {{ (old('marital_status', $inputs['marital_status'] ?? '') == 'single') ? 'selected' : '' }}>{{ __('Single') }}</option>
                                    <option value="married_joint" {{ (old('marital_status', $inputs['marital_status'] ?? '') == 'married_joint') ? 'selected' : '' }}>{{ __('Married (Joint Assessment)') }}</option>
                                </select>
                                @error('marital_status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3 d-flex align-items-center pt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_church_member" id="is_church_member" value="1" {{ (old('is_church_member', $inputs['is_church_member'] ?? false)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_church_member">
                                        {{ __('Are you a church member (subject to church tax)?') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Calculate Estimated Tax') }}</button>
                    </form>
                </div>
            </div>

            @if (session('tax_estimation_results'))
                @php $results = session('tax_estimation_results'); @endphp
                <div class="card shadow-sm p-4">
                    <h2>{{ __('Estimated Tax Calculation Results') }}</h2>
                     <p class="text-muted">{{__('For year')}}: {{ $results['expense_period_year_used'] ?? $currentYear }}</p>
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>{{ __('Your Annual Gross Income:') }}</td>
                                <td class="text-end">€{{ number_format($results['annual_gross_income'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Total Business Expenses (') }}{{ $results['expense_period_year_used'] ?? $currentYear }}{{ __('):') }}</td>
                                <td class="text-end">- €{{ number_format($results['total_business_expenses_calculated'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>{{ __('Estimated Annual Taxable Income:') }}</td>
                                <td class="text-end">€{{ number_format($results['annual_taxable_income_calculated'], 2, ',', '.') }}</td>
                            </tr>
                            <tr><td colspan="2">&nbsp;</td></tr>
                            <tr>
                                <td>{{ __('Calculated Income Tax (Lohnsteuer/Einkommensteuer):') }}</td>
                                <td class="text-end">€{{ number_format($results['income_tax'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Solidarity Surcharge (Solidaritätszuschlag):') }}</td>
                                <td class="text-end">€{{ number_format($results['solidarity_surcharge'], 2, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('Church Tax (Kirchensteuer):') }}</td>
                                <td class="text-end">€{{ number_format($results['church_tax'], 2, ',', '.') }}</td>
                            </tr>
                            <tr class="fw-bold table-primary">
                                <td>{{ __('Total Estimated Annual Tax:') }}</td>
                                <td class="text-end">€{{ number_format($results['total_tax'], 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @if (isset($results['error']))
                         <div class="alert alert-danger mt-3">{{ $results['error'] }}</div>
                    @endif
                    <small class="text-muted mt-3">{{ __('Note: This estimation is based on the simplified tax brackets and rules configured for') }} {{ config('tax_rates.germany.year') }}. {{ __('It does not include all possible deductions, allowances, or social security contributions.') }}</small>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
