<?php

namespace App\Services;

use Illuminate\Support\Facades\Log; // Optional: For debugging during development

class TaxService
{
    protected $taxConfig;
    protected $year;

    public function __construct()
    {
        $this->taxConfig = config('tax_rates.germany');
        if (!$this->taxConfig) {
            // Log an error or throw an exception if config is not found
            Log::error('German tax configuration (config/tax_rates.php) not found or is empty.');
            // Or throw new \Exception('German tax configuration not found.');
            $this->taxConfig = []; // Ensure it's an array to prevent errors on access
        }
        $this->year = $this->taxConfig['year'] ?? null;
    }

    /**
     * Calculate German taxes based on provided inputs.
     *
     * @param float $annualGrossIncome
     * @param float $totalBusinessExpenses
     * @param bool $isChurchMember
     * @param string $maritalStatus 'single' or 'married_joint'
     * @param string|null $stateAbbreviation Abbreviation for German federal state (e.g., 'BW', 'BY', 'BE')
     * @return array
     */
    public function calculateGermanTax(
        float $annualGrossIncome,
        float $totalBusinessExpenses,
        bool $isChurchMember,
        string $maritalStatus = 'single',
        ?string $stateAbbreviation = null
    ): array {
        if (empty($this->taxConfig) || empty($this->year)) {
            // Return an error structure or throw exception if config was not loaded properly
            return [
                'error' => 'Tax configuration not loaded. Cannot perform calculation.',
                'year' => null,
                'inputs' => compact('annualGrossIncome', 'totalBusinessExpenses', 'isChurchMember', 'maritalStatus', 'stateAbbreviation'),
                'calculations' => []
            ];
        }

        $zve = $annualGrossIncome - $totalBusinessExpenses;
        $zve = max(0.0, $zve); // Taxable income cannot be negative

        $incomeTax = $this->calculateIncomeTax($zve, $maritalStatus);
        $solidaritySurcharge = $this->calculateSolidaritySurcharge($zve, $incomeTax, $maritalStatus);
        $churchTax = $this->calculateChurchTax($incomeTax, $isChurchMember, $stateAbbreviation);

        $totalTaxLiability = $incomeTax + $solidaritySurcharge + $churchTax;

        return [
            'year' => $this->year,
            'inputs' => [
                'annualGrossIncome' => round($annualGrossIncome, 2),
                'totalBusinessExpenses' => round($totalBusinessExpenses, 2),
                'isChurchMember' => $isChurchMember,
                'maritalStatus' => $maritalStatus,
                'stateAbbreviation' => $stateAbbreviation,
            ],
            'calculations' => [
                'taxableIncomeZvE' => round($zve, 2),
                'incomeTax' => round($incomeTax, 2),
                'solidaritySurcharge' => round($solidaritySurcharge, 2),
                'churchTax' => round($churchTax, 2),
                'totalTaxLiability' => round($totalTaxLiability, 2),
            ]
        ];
    }

    protected function calculateIncomeTax(float $zve, string $maritalStatus = 'single'): float
    {
        $zveForCalculation = ($maritalStatus === 'married_joint') ? $zve / 2 : $zve;
        $calculatedTax = 0.0;

        $zones = $this->taxConfig['income_tax']['zones'] ?? [];

        foreach ($zones as $zone) {
            if ($zveForCalculation <= $zone['up_to_zve']) {
                switch ($zone['rate_type']) {
                    case 'zero':
                        $calculatedTax = 0.0;
                        break;
                    case 'progressive_y':
                        $y_zve_offset = $zone['y_zve_offset'];
                        $y_denominator = $zone['y_denominator'];
                        $y_factor_1 = $zone['y_factor_1'];
                        $y_factor_2 = $zone['y_factor_2'];
                        $add_amount = $zone['add_amount'];

                        $y = ($zveForCalculation - $y_zve_offset) / $y_denominator;
                        $calculatedTax = ($y_factor_1 * $y + $y_factor_2) * $y + $add_amount;
                        break;
                    case 'linear':
                        $rate = $zone['rate'];
                        $subtract_amount = $zone['subtract_amount'];
                        $calculatedTax = $zveForCalculation * $rate - $subtract_amount;
                        break;
                }
                break; // Found the correct zone, exit loop
            }
        }

        $calculatedTax = max(0.0, $calculatedTax); // Ensure tax is not negative
        $finalTax = ($maritalStatus === 'married_joint') ? $calculatedTax * 2 : $calculatedTax;

        return floor($finalTax); // German income tax is floored to full euros
    }

    protected function calculateSolidaritySurcharge(float $zve, float $incomeTaxAmount, string $maritalStatus = 'single'): float
    {
        if ($incomeTaxAmount <= 0) {
            return 0.0;
        }

        $config = $this->taxConfig['solidarity_surcharge'] ?? [];
        if (empty($config)) return 0.0;


        $exemptionZveThreshold = $config['exemption_zve'];
        $milderZoneMaxZve = $config['milder_zone_max_zve'];

        if ($maritalStatus === 'married_joint') {
            $exemptionZveThreshold *= 2;
            $milderZoneMaxZve *= 2;
        }

        if ($zve <= $exemptionZveThreshold) {
            return 0.0;
        }

        $soli = 0.0;
        if ($zve > $exemptionZveThreshold && $zve <= $milderZoneMaxZve) {
            $milderZoneDivisor = $milderZoneMaxZve - $exemptionZveThreshold;
            if ($milderZoneDivisor <= 0) { // Avoid division by zero or invalid range
                $soli = ($incomeTaxAmount * $config['rate']); // Fallback to full rate
            } else {
                $reductionFactor = 1 - (($zve - $exemptionZveThreshold) / $milderZoneDivisor);
                $soli = ($incomeTaxAmount * $config['rate']) * $reductionFactor;
            }
        } else { // $zve > $milderZoneMaxZve
            $soli = $incomeTaxAmount * $config['rate'];
        }

        return max(0.0, round($soli, 2)); // Rounded to 2 decimal places
    }

    protected function calculateChurchTax(float $incomeTaxAmount, bool $isChurchMember, ?string $stateAbbreviation = null): float
    {
        if (!$isChurchMember || $incomeTaxAmount <= 0) {
            return 0.0;
        }

        $config = $this->taxConfig['church_tax'] ?? [];
        if (empty($config)) return 0.0;

        $rate = $config['default_rate']; // Default rate

        if (!empty($stateAbbreviation)) {
            $stateUpper = strtoupper($stateAbbreviation);
            if ($stateUpper === 'BW' || $stateUpper === 'BY') {
                $rate = $config['rate_baden_wuerttemberg_bayern'];
            } else { // For any other provided state, assume the 'other_states' rate
                $rate = $config['rate_other_states'];
            }
        }

        $churchTax = $incomeTaxAmount * $rate;
        return max(0.0, round($churchTax, 2)); // Rounded to 2 decimal places
    }
}
