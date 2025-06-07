<?php

return [
    'germany' => [
        'year' => 2025,

        'income_tax' => [
            'grundfreibetrag' => 11604, // Basic allowance
            'zones' => [
                // Zone 0: Up to Grundfreibetrag
                ['up_to_zve' => 11604, 'rate_type' => 'zero'],

                // Zone 1 (Progressive): zvE from 11,605 to 66,760
                [
                    'up_to_zve' => 66760,
                    'rate_type' => 'progressive_y',
                    'y_factor_1' => 212.02, // Factor for y^2
                    'y_factor_2' => 2400,   // Factor for y
                    'add_amount' => 1000,
                    'y_zve_offset' => 11604,
                    'y_denominator' => 10000,
                ],
                // Zone 2 (Linear 42%): zvE from 66,761 to 277,825
                [
                    'up_to_zve' => 277825,
                    'rate_type' => 'linear',
                    'rate' => 0.42,
                    'subtract_amount' => 9328,
                ],
                // Zone 3 (Linear 45%): zvE over 277,825
                [
                    'up_to_zve' => PHP_INT_MAX, // Represents infinity
                    'rate_type' => 'linear',
                    'rate' => 0.45,
                    'subtract_amount' => 18307,
                ]
            ],
            // General note on how to apply zones:
            // Iterate through zones. If zvE <= zone.up_to_zve, apply that zone's calculation.
            // For 'progressive_y': y = (zvE - zone.y_zve_offset) / zone.y_denominator; tax = (zone.y_factor_1 * y + zone.y_factor_2) * y + zone.add_amount;
            // For 'linear': tax = zvE * zone.rate - zone.subtract_amount;
            // For 'zero': tax = 0;
        ],

        'solidarity_surcharge' => [
            'rate' => 0.055, // 5.5%
            'exemption_zve' => 18130, // No Soli if zvE <= this
            'milder_zone_max_zve' => 66761, // Milder zone applies for zvE > exemption_zve AND zvE <= milder_zone_max_zve
            // Formula for milder zone: Soli = (Calculated_Income_Tax * 0.055) * (1 - ((zvE - exemption_zve) / (milder_zone_max_zve - exemption_zve)))
            // Note: The divisor (48631 in previous notes) is milder_zone_max_zve - exemption_zve = 66761 - 18130 = 48631.
            // Full Soli if zvE > milder_zone_max_zve (and income_tax > 0)
        ],

        'church_tax' => [
            'rate_baden_wuerttemberg_bayern' => 0.08, // 8% for BW and Bayern
            'rate_other_states' => 0.09, // 9% for other states
            'default_rate' => 0.09, // Default if state not specified
            // Note: Applied on the calculated income tax.
        ],

        'social_security_freelancer' => [
            'health_insurance' => [
                'base_rate' => 0.146, // 14.6%
                'average_additional_rate' => 0.017, // Approx. 1.7% (provider dependent, this is an average for 2024/2025, may need update)
                // Total rate = base_rate + average_additional_rate. Freelancers pay this full combined rate.
                'income_ceiling_pa' => 69300, // Annual income ceiling
            ],
            'care_insurance' => [
                'rate_default' => 0.034, // 3.4%
                'rate_childless_over_23' => 0.040, // 4.0% (0.6% surcharge)
                // Freelancers pay this full rate.
                'income_ceiling_pa' => 69300, // Annual income ceiling
            ],
            'pension_insurance' => [
                // Voluntary for most freelancers, but some professions are mandatory.
                'rate' => 0.186, // 18.6%
                'income_ceiling_pa_west' => 87600,
                'income_ceiling_pa_east' => 85200,
                'default_income_ceiling_pa' => 87600, // Assuming West as default if not specified
            ],
            'note_deductibility' => 'Portions of health and care insurance may be deductible as special expenses or business expenses, affecting taxable income (zvE). This config primarily stores the contribution rates themselves.'
        ],

        'notes' => [
            'All figures are for the year 2025 unless otherwise specified.',
            'This configuration is based on research from docs/tax_research/german_tax_2025_summary.md.',
            'Consult official BMF documents and a tax advisor for definitive guidance.'
        ]
    ]
];
