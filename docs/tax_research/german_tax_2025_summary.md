# Summary of German Tax Information for 2025 (Preliminary)

This document summarizes the initial findings for German tax regulations applicable to 2025, based on user-provided text and new information. It also highlights critical missing information required for accurate tax calculations.

## 1. Basic Allowance (Grundfreibetrag) 2025
*   **Single (Tax Class 1, 4, etc.):** €11,604
*   **Married (Tax Class 3, joint filing):** €23,208 (double the single allowance)

## 2. Income Tax Rates and Formulas (Lohnsteuer / Einkommensteuer) 2025
Germany uses a progressive tax rate system. The rates for taxable income (zu versteuerndes Einkommen - `zvE`) are as follows, based on §32a EStG 2025:

### 2.1 For Single Filers:
*   **Zone 0 (Basic Allowance):** For `zvE` up to €11,604:
    *   `Income Tax = 0`
*   **Zone 1 (Progressive):** For `zvE` from €11,605 to €66,760:
    *   `y = (zvE - 11,604) / 10,000`
    *   `Income Tax = (212.02 * y + 2,400) * y + 1,000` (Note: Result should be rounded down to the nearest Euro)
*   **Zone 2 (Linear 42%):** For `zvE` from €66,761 to €277,825:
    *   `Income Tax = 0.42 * zvE - 9,328` (Note: Result should be rounded down to the nearest Euro)
*   **Zone 3 (Linear 45%):** For `zvE` over €277,825:
    *   `Income Tax = 0.45 * zvE - 18,307` (Note: Result should be rounded down to the nearest Euro)

### 2.2 For Married / Joint Filers (Splittingverfahren):
The income tax for jointly assessed spouses is calculated by applying the single filer formulas to half of their joint `zvE`, and then doubling the resulting tax amount.
Effectively, the thresholds for the zones are doubled:
*   **Zone 0 (Basic Allowance):** For joint `zvE` up to €23,208: Tax = 0
*   **Zone 1 (Progressive):** For joint `zvE` from €23,209 to €133,520:
    *   `y = ( (0.5 * zvE) - 11,604) / 10,000`
    *   `Single_Person_Tax = (212.02 * y + 2,400) * y + 1,000`
    *   `Joint_Income_Tax = 2 * floor(Single_Person_Tax)`
*   **Zone 2 (Linear 42%):** For joint `zvE` from €133,521 to €555,650:
    *   `Single_Person_Tax = 0.42 * (0.5 * zvE) - 9,328`
    *   `Joint_Income_Tax = 2 * floor(Single_Person_Tax)`
*   **Zone 3 (Linear 45%):** For joint `zvE` over €555,650:
    *   `Single_Person_Tax = 0.45 * (0.5 * zvE) - 18,307`
    *   `Joint_Income_Tax = 2 * floor(Single_Person_Tax)`

## 3. Solidarity Surcharge (Solidaritätszuschlag - Soli) 2025
Based on Solidaritätszuschlaggesetz 2025 (SolzG). Let `Calculated_Income_Tax` be the result from the income tax formulas above (before any church tax).

*   **Exemption based on Taxable Income (zvE):**
    *   **Single Filers:** No Soli if `zvE <= €18,130`.
    *   **Married/Joint Filers:** No Soli if joint `zvE <= €36,260`.
*   **Milder Zone (Gleitzone):**
    *   **Single Filers:** Applies for `zvE` from €18,131 to €66,761.
        *   `Reduction_Factor = 1 - ((zvE - 18130) / 48631)` (Note: The denominator 48631 is €66,761 - €18,130)
        *   `Soli = (Calculated_Income_Tax * 0.055) * Reduction_Factor`
        *   (The Soli should not exceed 20% of the difference between `Calculated_Income_Tax` and the income tax threshold for Soli, which is €18,130 for singles. This is a cap within the milder zone, needs further formula detail if this cap is different from the main formula's output).
    *   **Married/Joint Filers:** Applies for joint `zvE` from €36,261 to €133,522 (double the single zvE thresholds).
        *   `Reduction_Factor = 1 - ((Joint_zvE - 36260) / 97262)` (Note: Denominator is €133,522 - €36,260)
        *   `Soli = (Calculated_Joint_Income_Tax * 0.055) * Reduction_Factor`
*   **Full Rate:**
    *   **Single Filers:** For `zvE > €66,761`.
        *   `Soli = Calculated_Income_Tax * 0.055`
    *   **Married/Joint Filers:** For joint `zvE > €133,522`.
        *   `Soli = Calculated_Joint_Income_Tax * 0.055`
*   **Important Clarification Needed:** The previous information mentioned Soli exemption based on "income tax amount" (€18,130 of tax for singles). The new information implies the €18,130 is `zvE`. The milder zone formula uses `zvE` for its range and reduction factor, but applies the 5.5% to the `Calculated_Income_Tax`. This summary now reflects `zvE` as the primary driver for exemption and zone definition, but this interaction needs to be absolutely confirmed.

## 4. Church Tax (Kirchensteuer) 2025
*   **Rate:** Typically 8% or 9% of the `Calculated_Income_Tax` (from section 2).
*   **Dependency:** Rate depends on the federal state (Bundesland).
*   **Applicability:** Applies only to individuals who are registered members of a church that levies this tax. (The income tax is reduced by child allowances before calculating church tax, if applicable - this detail needs confirmation for precise calculation order).

## 5. Other Allowances and Lump Sums (Contextual)
*   **Employee Lump Sum (Arbeitnehmer-Pauschbetrag):** €1,230 (Updated from previous €1,000 value, typically for employees, for 2023/2024, needs 2025 confirmation).
*   **Standard Special Expenses Lump Sum (Sonderausgaben-Pauschbetrag):** €36 (for singles) / €72 (for married/joint).
*   **Single Parent Relief Amount (Alleinerziehendenentlastungsbetrag):** €4,260 (applies to Tax Class 2).
*   **Child Allowance (Kinderfreibetrag) 2025:** €9,312 per child (or €4,656 if split). (Updated from previous €9,540). Used in Günstigerprüfung against Kindergeld.

## 6. Social Security Contributions for Freelancers/Self-Employed (2025)
Freelancers typically pay the full contribution (equivalent to employer + employee shares for many items).
*   **Pension Insurance (Rentenversicherung):**
    *   Rate: 18.6%.
    *   Income Assessment Ceiling: €87,600 p.a. (West Germany) / €85,200 p.a. (East Germany).
    *   *Note: Often voluntary for freelancers unless specific professions. Mandatory for some (e.g. artists, publicists via KSK).*
*   **Unemployment Insurance (Arbeitslosenversicherung):**
    *   Rate: 2.6% (if opted-in). Ceiling: same as pension insurance.
    *   *Note: Typically voluntary for freelancers.*
*   **Health Insurance (Krankenversicherung - GKV):**
    *   General Rate: ~14.6% + Average Additional Contribution from provider (~1.7% for 2024, ~1.6% seems to be a general figure, requires 2025 update for specific average additional rate). Total ~16.2% - ~16.3%.
    *   Income Assessment Ceiling: €69,300 p.a. (€5,775 per month).
    *   *Note: 50% of health insurance premiums (up to a certain limit) may be deductible as special expenses (Sonderausgaben) or business expenses, impacting `zvE`.*
*   **Long-Term Care Insurance (Pflegeversicherung):**
    *   Rate: 3.4%.
    *   Surcharge for childless individuals over 23: +0.6% (total 4.0%).
    *   Income Assessment Ceiling: €69,300 p.a. (€5,775 per month).
    *   *Note: Similar deductibility rules as health insurance may apply.*

## Critical Missing / Needs Clarification Information:

1.  **Income Tax Progressive Zone Formula (2025):** **FOUND and updated.**
2.  **Solidarity Surcharge Exemption Threshold (2025):** Updated to `zvE <= €18,130` (single) / `zvE <= €36,260` (married). **Clarification still needed:** The exact interaction between `zvE`-based exemption/zone definition and the calculation being `Calculated_Income_Tax * 0.055 * Factor`. Also, the cap within the milder zone (e.g., Soli not exceeding 20% of difference between tax and threshold) needs precise formula.
3.  **Solidarity Surcharge Milder Zone (Gleitzone) Formula (2025):** **FOUND and updated.** The upper `zvE` limit for the milder zone (€66,761 for singles) seems to align with where full Soli would begin if based purely on `zvE`.
4.  **Social Security Contributions for Freelancers (2025):**
    *   **Partially Updated.** Confirmed 2025 general rates and ceilings for Health, Care, Pension.
    *   **Still Needed:**
        *   Precise average additional contribution for GKV Health Insurance for 2025 (if a specific average should be used, or if it remains variable by provider).
        *   Confirmation on the exact deductibility rules for health/care insurance for freelancers (as business expenses vs. special expenses and how it affects `zvE` calculation for income tax).
5.  **List of applicable states for 8% vs 9% Church Tax.** (Remains missing)
6.  **Employee Lump Sum (Arbeitnehmer-Pauschbetrag) for 2025:** Needs confirmation if €1,230 is the final 2025 figure.
7.  **Rounding Rules:** Explicit confirmation on rounding rules for intermediate calculations vs. final tax amounts (e.g., income tax rounded down to nearest Euro, Soli/Church tax rounded to nearest cent).

---
This document will be updated as more precise information becomes available.
