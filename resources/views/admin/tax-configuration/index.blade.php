@extends('layouts.app') 

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Tax Configuration') }}
    </h2>
@endsection

@section('content')
<div class="container py-4">
    <h1>{{ __('Tax Configuration (Germany') }} {{ $taxConfig['year'] ?? 'N/A' }})</h1>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="alert alert-info">
        <p><strong>{{ __('Note') }}:</strong> {{ __('This page displays the current tax configuration loaded by the application from the') }} <code>config/tax_rates.php</code> {{ __('file.') }}</p>
        <p>{{ __('To update these settings, you currently need to manually edit the') }} <code>config/tax_rates.php</code> {{ __('file on the server and ensure the changes are deployed. After updating the file, you can try clearing the configuration cache below. In some environments, a server restart or') }} <code>php artisan config:cache</code> {{ __('(in production) might be necessary for changes to take full effect.') }}</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ __('Current Tax Configuration Source') }}</div>
        <div class="card-body">
            <p>{{ __('Data is read from:') }} <code>config/tax_rates.php</code></p>
            <form action="{{ route('admin.tax-configuration.clear-cache') }}" method="POST" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-warning">{{ __('Attempt to Clear Configuration Cache') }}</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ __('Income Tax Brackets (Single Filer Basis)') }}</div>
        <div class="card-body">
            @if (!empty($taxConfig['income_tax']['single_brackets']))
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Taxable Income Threshold (€)') }}</th>
                            <th>{{ __('Marginal Tax Rate (%)') }}</th>
                            <th>{{ __('Base Tax at Threshold (€)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($taxConfig['income_tax']['single_brackets'] as $index => $bracket)
                            <tr>
                                <td>{{ $bracket['limit'] == 0 ? __('Up to') . ' ' . number_format($bracket['limit'], 0, ',', '.') : __('Above') . ' ' . number_format($bracket['limit'], 0, ',', '.') }}</td>
                                <td>{{ number_format($bracket['rate'] * 100, 2, ',', '.') }}%</td>
                                <td>{{ number_format($bracket['base_tax'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <small>{{ __('Note: This is a simplified representation. Actual German income tax uses complex formulas. For \'Married (Joint Assessment)\', income is typically halved, tax calculated using single brackets, then tax amount is doubled (Splittingverfahren).') }}</small>
            @else
                <p class="text-danger">{{ __('Income tax brackets are not configured or not found.') }}</p>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">{{ __('Solidarity Surcharge') }}</div>
                <div class="card-body">
                    @if (!empty($taxConfig['income_tax']['solidarity_surcharge']))
                        <p>{{ __('Rate') }}: {{ number_format(($taxConfig['income_tax']['solidarity_surcharge']['rate'] ?? 0) * 100, 1, ',', '.') }}% {{ __('of calculated income tax') }}</p>
                        <p>{{ __('No surcharge if tax <=') }} €{{ number_format($taxConfig['income_tax']['solidarity_surcharge']['threshold_single_no_surcharge'] ?? 0, 0, ',', '.') }}</p>
                        <p>{{ __('Full surcharge if tax >') }} €{{ number_format($taxConfig['income_tax']['solidarity_surcharge']['threshold_single_full_surcharge'] ?? 0, 0, ',', '.') }}</p>
                    @else
                        <p class="text-danger">{{ __('Solidarity surcharge configuration not found.') }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">{{ __('Church Tax') }}</div>
                <div class="card-body">
                     @if (!empty($taxConfig['income_tax']['church_tax']))
                        <p>{{ __('Rate') }}: {{ number_format(($taxConfig['income_tax']['church_tax']['rate'] ?? 0) * 100, 0) }}% {{ __('of calculated income tax (if member)') }}</p>
                    @else
                        <p class="text-danger">{{ __('Church tax configuration not found.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">{{ __('VAT (Mehrwertsteuer - MwSt)') }}</div>
        <div class="card-body">
            @if (!empty($taxConfig['vat']))
                <p>{{ __('Standard Rate') }}: {{ number_format(($taxConfig['vat']['standard'] ?? 0) * 100, 0) }}%</p>
                <p>{{ __('Reduced Rate') }}: {{ number_format(($taxConfig['vat']['reduced'] ?? 0) * 100, 0) }}%</p>
            @else
                <p class="text-danger">{{ __('VAT configuration not found.') }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">{{ __('Social Security Contribution Rates (Illustrative - For Reference)') }}</div>
        <div class="card-body">
            @if (!empty($taxConfig['social_security']))
                <p>{{ __('Health Insurance (General)') }}: {{ number_format(($taxConfig['social_security']['health_insurance_general_rate'] ?? 0) * 100, 1, ',', '.') }}% + {{ __('Avg. Additional') }} {{ number_format(($taxConfig['social_security']['health_insurance_avg_additional_rate'] ?? 0) * 100, 1, ',', '.') }}%</p>
                <p>{{ __('Pension Insurance') }}: {{ number_format(($taxConfig['social_security']['pension_insurance_rate'] ?? 0) * 100, 1, ',', '.') }}%</p>
                <p>{{ __('Unemployment Insurance') }}: {{ number_format(($taxConfig['social_security']['unemployment_insurance_rate'] ?? 0) * 100, 1, ',', '.') }}%</p>
                <p>{{ __('Long-term Care Insurance') }}: {{ number_format(($taxConfig['social_security']['long_term_care_insurance_rate'] ?? 0) * 100, 1, ',', '.') }}% (+ {{ number_format(($taxConfig['social_security']['long_term_care_childless_surcharge'] ?? 0) * 100, 1, ',', '.') }}% {{ __('surcharge for childless >= 23 yrs') }})</p>
                <h5 class="mt-3">{{ __('Income Ceilings (Beitragsbemessungsgrenzen - Annual Examples)') }}</h5>
                <p>{{ __('Pension/Unemployment (West)') }}: €{{ number_format($taxConfig['social_security']['pension_unemployment_ceiling_west'] ?? 0, 0, ',', '.') }}</p>
                <p>{{ __('Pension/Unemployment (East)') }}: €{{ number_format($taxConfig['social_security']['pension_unemployment_ceiling_east'] ?? 0, 0, ',', '.') }}</p>
                <p>{{ __('Health/Long-term Care') }}: €{{ number_format($taxConfig['social_security']['health_long_term_care_ceiling'] ?? 0, 0, ',', '.') }}</p>
                <small class="text-muted">{{ __('Note: Social security calculations are complex, involve employer/employee shares, and various income ceilings. These are simplified reference rates.') }}</small>
            @else
                <p class="text-danger">{{ __('Social security configuration not found.') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection
