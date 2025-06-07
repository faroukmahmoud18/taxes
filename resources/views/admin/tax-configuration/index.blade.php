@extends('layouts.app') 

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        {{ __('Tax Configuration') }}
    </h2>
@endsection

@section('content')
<div class="container py-4">
    <h1>{{ __('Tax Configuration (Germany)') }} {{ is_array($taxConfig) && isset($taxConfig['year']) ? $taxConfig['year'] : '' }}</h1>

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

    @if (!empty($taxConfig) && is_array($taxConfig))
        <!-- General Information -->
        <div class="card mb-4">
            <div class="card-header">{{ __('General Information') }}</div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">{{ __('Configuration Year') }}</dt>
                    <dd class="col-sm-9">{{ $taxConfig['year'] ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>

        <!-- Income Tax Details -->
        <div class="card mb-4">
            <div class="card-header">{{ __('Income Tax Details') }}</div>
            <div class="card-body">
                @php $incomeTaxConfig = $taxConfig['income_tax'] ?? []; @endphp
                <dl class="row mb-3">
                    <dt class="col-sm-3">{{ __('Basic Allowance (Grundfreibetrag)') }}</dt>
                    <dd class="col-sm-9">€{{ number_format($incomeTaxConfig['grundfreibetrag'] ?? 0, 2, ',', '.') }}</dd>
                </dl>

                @if (!empty($incomeTaxConfig['zones']))
                    <h4>{{ __('Income Tax Zones (Single Filer Basis)') }}</h4>
                    @foreach ($incomeTaxConfig['zones'] as $index => $zone)
                        <div class="mb-3 p-3 border rounded bg-light">
                            <h5>{{ __('Zone') }} {{ $index }} <small class="text-muted"><em>({{ $zone['rate_type'] }})</em></small></h5>
                            <dl class="row">
                                <dt class="col-sm-4">{{ __('Applies up to ZVE') }}</dt>
                                <dd class="col-sm-8">
                                    @if ($zone['up_to_zve'] === PHP_INT_MAX)
                                        {{ __('Over previous threshold') }}
                                    @else
                                        €{{ number_format($zone['up_to_zve'], 2, ',', '.') }}
                                    @endif
                                </dd>

                                <dt class="col-sm-4">{{ __('Rate Type') }}</dt>
                                <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $zone['rate_type'])) }}</dd>

                                @if ($zone['rate_type'] === 'progressive_y')
                                    <dt class="col-sm-4">{{ __('Y ZVE Offset') }}</dt>
                                    <dd class="col-sm-8">€{{ number_format($zone['y_zve_offset'], 2, ',', '.') }}</dd>
                                    <dt class="col-sm-4">{{ __('Y Denominator') }}</dt>
                                    <dd class="col-sm-8">{{ number_format($zone['y_denominator'], 0, ',', '.') }}</dd>
                                    <dt class="col-sm-4">{{ __('Y Factor 1 (for y²)') }}</dt>
                                    <dd class="col-sm-8">{{ $zone['y_factor_1'] }}</dd>
                                    <dt class="col-sm-4">{{ __('Y Factor 2 (for y)') }}</dt>
                                    <dd class="col-sm-8">{{ $zone['y_factor_2'] }}</dd>
                                    <dt class="col-sm-4">{{ __('Add Amount') }}</dt>
                                    <dd class="col-sm-8">€{{ number_format($zone['add_amount'], 2, ',', '.') }}</dd>
                                    <dt class="col-sm-4">{{ __('Formula') }}</dt>
                                    <dd class="col-sm-8"><small><code>y = (zvE - {{ $zone['y_zve_offset'] }}) / {{ $zone['y_denominator'] }};<br>Tax = ({{ $zone['y_factor_1'] }} * y + {{ $zone['y_factor_2'] }}) * y + {{ $zone['add_amount'] }}</code></small></dd>
                                @elseif ($zone['rate_type'] === 'linear')
                                    <dt class="col-sm-4">{{ __('Rate') }}</dt>
                                    <dd class="col-sm-8">{{ ($zone['rate'] * 100) }}%</dd>
                                    <dt class="col-sm-4">{{ __('Subtract Amount') }}</dt>
                                    <dd class="col-sm-8">€{{ number_format($zone['subtract_amount'], 2, ',', '.') }}</dd>
                                    <dt class="col-sm-4">{{ __('Formula') }}</dt>
                                    <dd class="col-sm-8"><small><code>Tax = zvE * {{ $zone['rate'] }} - {{ $zone['subtract_amount'] }}</code></small></dd>
                                @elseif ($zone['rate_type'] === 'zero')
                                     <dt class="col-sm-4">{{ __('Tax Rate') }}</dt>
                                     <dd class="col-sm-8">0%</dd>
                                @endif
                            </dl>
                        </div>
                    @endforeach
                    <small class="form-text text-muted">{{__('For married/joint filers (Splittingverfahren), ZVE is halved, tax calculated using single rates, then tax amount is doubled.')}}</small>
                @else
                    <p class="text-danger">{{ __('Income tax zone configuration not found.') }}</p>
                @endif
            </div>
        </div>

        <!-- Solidarity Surcharge -->
        <div class="card mb-4">
            <div class="card-header">{{ __('Solidarity Surcharge (Solidaritätszuschlag)') }}</div>
            <div class="card-body">
                @php $soliConfig = $taxConfig['solidarity_surcharge'] ?? []; @endphp
                @if (!empty($soliConfig))
                    <dl class="row">
                        <dt class="col-sm-4">{{ __('Rate') }}</dt>
                        <dd class="col-sm-8">{{ ($soliConfig['rate'] ?? 0) * 100 }}% (of calculated income tax)</dd>

                        <dt class="col-sm-4">{{ __('Exemption ZVE (Single)') }}</dt>
                        <dd class="col-sm-8">€{{ number_format($soliConfig['exemption_zve'] ?? 0, 2, ',', '.') }} (No Soli if ZVE ≤ this)</dd>

                        <dt class="col-sm-4">{{ __('Milder Zone Max ZVE (Single)') }}</dt>
                        <dd class="col-sm-8">€{{ number_format($soliConfig['milder_zone_max_zve'] ?? 0, 2, ',', '.') }} (Milder zone if ZVE > exemption and ZVE ≤ this)</dd>

                        <dt class="col-sm-4">{{ __('Milder Zone Formula') }}</dt>
                        <dd class="col-sm-8"><small><code>Soli = (IncomeTax * Rate) * (1 - ((ZVE - ExemptionZVE) / (MilderZoneMaxZVE - ExemptionZVE)))</code></small></dd>
                        <dd class="col-sm-12"><small class="text-muted">{{__('Note: For married/joint filers, ZVE thresholds for exemption and milder zone are typically doubled.')}}</small></dd>
                    </dl>
                @else
                    <p class="text-danger">{{ __('Solidarity surcharge configuration not found.') }}</p>
                @endif
            </div>
        </div>

        <!-- Church Tax -->
        <div class="card mb-4">
            <div class="card-header">{{ __('Church Tax (Kirchensteuer)') }}</div>
            <div class="card-body">
                @php $churchTaxConfig = $taxConfig['church_tax'] ?? []; @endphp
                @if (!empty($churchTaxConfig))
                    <dl class="row">
                        <dt class="col-sm-4">{{ __('Rate (Baden-Württemberg/Bayern)') }}</dt>
                        <dd class="col-sm-8">{{ ($churchTaxConfig['rate_baden_wuerttemberg_bayern'] ?? 0) * 100 }}%</dd>
                        <dt class="col-sm-4">{{ __('Rate (Other States)') }}</dt>
                        <dd class="col-sm-8">{{ ($churchTaxConfig['rate_other_states'] ?? 0) * 100 }}%</dd>
                        <dt class="col-sm-4">{{ __('Default Rate (if state not specified)') }}</dt>
                        <dd class="col-sm-8">{{ ($churchTaxConfig['default_rate'] ?? 0) * 100 }}%</dd>
                        <dd class="col-sm-12"><small class="text-muted">{{__('Applied on the calculated income tax for church members.')}}</small></dd>
                    </dl>
                @else
                     <p class="text-danger">{{ __('Church tax configuration not found.') }}</p>
                @endif
            </div>
        </div>

        <!-- Social Security Freelancer -->
        <div class="card mb-4">
            <div class="card-header">{{ __('Social Security Contributions (Freelancer Basis)') }}</div>
            <div class="card-body">
                @php $ssConfig = $taxConfig['social_security_freelancer'] ?? []; @endphp
                @if (!empty($ssConfig))
                    @foreach ($ssConfig as $key => $details)
                        @if(is_array($details))
                            <h5 class="mt-3">{{ __(Str::title(str_replace('_', ' ', $key))) }}</h5>
                            <dl class="row">
                                @if(isset($details['base_rate']))
                                    <dt class="col-sm-5">{{ __('Base Rate') }}</dt>
                                    <dd class="col-sm-7">{{ ($details['base_rate'] * 100) }}%</dd>
                                @endif
                                @if(isset($details['average_additional_rate']))
                                    <dt class="col-sm-5">{{ __('Avg. Additional Rate') }}</dt>
                                    <dd class="col-sm-7">{{ ($details['average_additional_rate'] * 100) }}% (Total: {{ ($details['base_rate'] + $details['average_additional_rate'])*100 }}%)</dd>
                                @endif
                                @if(isset($details['rate']))
                                    <dt class="col-sm-5">{{ __('Rate') }}</dt>
                                    <dd class="col-sm-7">{{ ($details['rate'] * 100) }}%</dd>
                                @endif
                                @if(isset($details['rate_default']))
                                    <dt class="col-sm-5">{{ __('Default Rate') }}</dt>
                                    <dd class="col-sm-7">{{ ($details['rate_default'] * 100) }}%</dd>
                                @endif
                                @if(isset($details['rate_childless_over_23']))
                                    <dt class="col-sm-5">{{ __('Rate (Childless >23y)') }}</dt>
                                    <dd class="col-sm-7">{{ ($details['rate_childless_over_23'] * 100) }}%</dd>
                                @endif
                                 @if(isset($details['income_ceiling_pa']))
                                    <dt class="col-sm-5">{{ __('Income Ceiling (p.a.)') }}</dt>
                                    <dd class="col-sm-7">€{{ number_format($details['income_ceiling_pa'], 0, ',', '.') }}</dd>
                                @endif
                                @if(isset($details['income_ceiling_pa_west']))
                                    <dt class="col-sm-5">{{ __('Income Ceiling West (p.a.)') }}</dt>
                                    <dd class="col-sm-7">€{{ number_format($details['income_ceiling_pa_west'], 0, ',', '.') }}</dd>
                                @endif
                                @if(isset($details['income_ceiling_pa_east']))
                                    <dt class="col-sm-5">{{ __('Income Ceiling East (p.a.)') }}</dt>
                                    <dd class="col-sm-7">€{{ number_format($details['income_ceiling_pa_east'], 0, ',', '.') }}</dd>
                                @endif
                            </dl>
                        @elseif ($key === 'note_deductibility')
                             <p class="mt-3"><small class="text-muted"><strong>{{ __('Note') }}:</strong> {{ $details }}</small></p>
                        @endif
                    @endforeach
                @else
                    <p class="text-danger">{{ __('Social security configuration for freelancers not found.') }}</p>
                @endif
            </div>
        </div>

        <!-- Notes -->
        @if(!empty($taxConfig['notes']))
        <div class="card">
            <div class="card-header">{{ __('Configuration Notes') }}</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    @foreach($taxConfig['notes'] as $note)
                        <li class="list-group-item"><small>{{ $note }}</small></li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    @else
        <div class="alert alert-danger" role="alert">
            {{ __('Tax configuration data (config/tax_rates.php) not found or is empty/invalid.') }}
        </div>
    @endif

    {{--
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
    --}}
</div>
@endsection
