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
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-md-4 pt-md-2"> {{-- Adjusted top padding for medium screens and up --}}
                                    <input class="form-check-input @error('is_church_member') is-invalid @enderror" type="checkbox" value="1" id="is_church_member" name="is_church_member" {{ old('is_church_member', $inputs['is_church_member'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_church_member">
                                        {{ __('Are you a church member (subject to church tax)?') }}
                                    </label>
                                    @error('is_church_member')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="state_abbreviation" class="form-label">{{ __('Your State (Bundesland - 2-letter abbreviation for Church Tax)') }}</label>
                                <input type="text" class="form-control @error('state_abbreviation') is-invalid @enderror" id="state_abbreviation" name="state_abbreviation" value="{{ old('state_abbreviation', $inputs['state_abbreviation'] ?? '') }}" placeholder="E.g., BY, NW, BE (optional)">
                                @error('state_abbreviation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('Relevant for church tax calculation if you are a member. E.g., BY for Bavaria (8%), others usually 9%.') }}</small>
                            </div>
                            {{-- You can add another field here in col-md-6 if needed or leave it for spacing --}}
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('Calculate Estimated Tax') }}</button>
                    </form>
                </div>
            </div>

            @if (session('tax_estimation_results'))
                @php $calcResults = session('tax_estimation_results'); @endphp
                <div class="card shadow-sm p-4">
                    <h2>{{ __('Estimated Tax Calculation Results') }}</h2>
                     <p class="text-muted">{{__('For year')}}: {{ $calcResults['year'] ?? 'N/A' }}</p>

                    @if (isset($calcResults['error']))
                         <div class="alert alert-danger mt-3">{{ $calcResults['error'] }}</div>
                    @else
                        <h5>{{ __('Inputs Used:') }}</h5>
                        <table class="table table-sm table-borderless mb-3">
                            <tbody>
                                <tr><td style="width: 60%;">{{ __('Your Annual Gross Income:') }}</td><td class="text-end">€{{ number_format($calcResults['inputs']['annualGrossIncome'] ?? 0, 2, ',', '.') }}</td></tr>
                                <tr><td>{{ __('Total Business Expenses (') }}{{ $calcResults['inputs']['expense_period_year'] ?? $calcResults['year'] }}{{ __('):') }}</td><td class="text-end">- €{{ number_format($calcResults['inputs']['totalBusinessExpenses'] ?? 0, 2, ',', '.') }}</td></tr>
                                <tr><td>{{ __('Marital Status:') }}</td><td class="text-end">{{ __(ucfirst(str_replace('_', ' ', $calcResults['inputs']['maritalStatus'] ?? 'single'))) }}</td></tr>
                                <tr><td>{{ __('Church Member:') }}</td><td class="text-end">{{ ($calcResults['inputs']['isChurchMember'] ?? false) ? __('Yes') : __('No') }}</td></tr>
                                @if(!empty($calcResults['inputs']['stateAbbreviation']))
                                    <tr><td>{{ __('State Abbreviation for Church Tax:') }}</td><td class="text-end">{{ strtoupper($calcResults['inputs']['stateAbbreviation']) }}</td></tr>
                                @endif
                            </tbody>
                        </table>

                        <hr>
                        <h5>{{ __('Estimated Taxes:') }}</h5>
                        <table class="table table-sm">
                            <tbody>
                                <tr class="fw-bold">
                                    <td>{{ __('Estimated Annual Taxable Income (zvE):') }}</td>
                                    <td class="text-end">€{{ number_format($calcResults['calculations']['taxableIncomeZvE'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                                <tr><td colspan="2">&nbsp;</td></tr>
                                <tr>
                                    <td>{{ __('Calculated Income Tax (Lohnsteuer/Einkommensteuer):') }}</td>
                                    <td class="text-end">€{{ number_format($calcResults['calculations']['incomeTax'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Solidarity Surcharge (Solidaritätszuschlag):') }}</td>
                                    <td class="text-end">€{{ number_format($calcResults['calculations']['solidaritySurcharge'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>{{ __('Church Tax (Kirchensteuer):') }}</td>
                                    <td class="text-end">€{{ number_format($calcResults['calculations']['churchTax'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                                <tr class="fw-bold table-primary">
                                    <td>{{ __('Total Estimated Annual Tax:') }}</td>
                                    <td class="text-end">€{{ number_format($calcResults['calculations']['totalTaxLiability'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endif
                    <small class="text-muted mt-3 d-block">
                        {{ __('Note: This estimation is based on the tax formulas and parameters configured for the year') }} {{ $calcResults['year'] ?? config('tax_rates.germany.year', 'N/A') }}.
                        {{ __('It does not include all possible deductions or social security contributions. For precise calculations and advice, please consult a qualified tax advisor.') }}
                    </small>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
