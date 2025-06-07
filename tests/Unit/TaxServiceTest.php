<?php

namespace Tests\Unit;

use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase; // Not strictly needed for unit tests unless config is mocked via DB
use Tests\TestCase;

class TaxServiceTest extends TestCase
{
    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure fresh config is loaded if it's modified by other tests, though typically config is immutable per run.
        // If using a separate test config, ensure it's loaded here.
        // For now, we rely on the default config/tax_rates.php being present and correct.
        $this->taxService = new TaxService();
    }

    // Helper to assert tax components
    private function assertTaxComponents(array $result, float $expectedIncomeTax, float $expectedSoli, float $expectedChurchTax, ?string $message = null)
    {
        $this->assertEquals($expectedIncomeTax, $result['calculations']['incomeTax'], $message . ' - Income Tax Mismatch');
        $this->assertEquals($expectedSoli, $result['calculations']['solidaritySurcharge'], $message . ' - Soli Mismatch');
        $this->assertEquals($expectedChurchTax, $result['calculations']['churchTax'], $message . ' - Church Tax Mismatch');
        $totalExpected = $expectedIncomeTax + $expectedSoli + $expectedChurchTax;
        $this->assertEquals(round($totalExpected,2), $result['calculations']['totalTaxLiability'], $message . ' - Total Tax Mismatch');
    }

    // --- INCOME TAX TESTS ---

    public function testIncomeTax_Single_BelowGrundfreibetrag()
    {
        $result = $this->taxService->calculateGermanTax(10000, 0, false, 'single');
        $this->assertTaxComponents($result, 0.00, 0.00, 0.00, 'Single, ZVE 10k');
        $this->assertEquals(10000, $result['calculations']['taxableIncomeZvE']);
    }

    public function testIncomeTax_Single_ProgressiveZone_Example1()
    {
        // zvE = 20000. Expected Income Tax: y = (20000-11604)/10000 = 0.8396. Tax = (212.02 * 0.8396 + 2400) * 0.8396 + 1000 = (177.013992 + 2400) * 0.8396 + 1000 = 2577.013992 * 0.8396 + 1000 = 2163.6609 + 1000 = 3163.6609. Floored = 3163.00
        $result = $this->taxService->calculateGermanTax(20000, 0, false, 'single');
        // Soli for Tax=3163, ZVE=20000: Exemption ZVE=18130. It is in milder zone.
        // ReductionFactor = 1 - ((20000 - 18130) / (66761 - 18130)) = 1 - (1870 / 48631) = 1 - 0.038448... = 0.961551...
        // Soli = (3163 * 0.055) * 0.961551 = 173.965 * 0.961551 = 167.269... Rounded = 167.27
        $this->assertTaxComponents($result, 3163.00, 167.27, 0.00, 'Single, ZVE 20k');
    }

    public function testIncomeTax_Single_ProgressiveZone_Example2_High()
    {
        // zvE = 60000. y = (60000-11604)/10000 = 4.8396. Tax = (212.02 * 4.8396 + 2400) * 4.8396 + 1000 = (1026.092792 + 2400) * 4.8396 + 1000 = 3426.092792 * 4.8396 + 1000 = 16576.3019 + 1000 = 17576.3019. Floored = 17576.00
        $result = $this->taxService->calculateGermanTax(60000, 0, false, 'single');
        // Soli: Tax is 17576. zvE is 60000. Exemption 18130. Milder zone 18131-66761.
        // ReductionFactor = 1 - ((60000 - 18130) / (66761 - 18130)) = 1 - (41870 / 48631) = 1 - 0.860971... = 0.139028...
        // Soli = (17576 * 0.055) * 0.139028 = 966.68 * 0.139028 = 134.385... Rounded = 134.39
        $this->assertTaxComponents($result, 17576.00, 134.39, 0.00, 'Single, ZVE 60k');
    }

    public function testIncomeTax_Single_Linear42Zone_Example1()
    {
        // zvE = 70000. Tax = 0.42 * 70000 - 9328 = 29400 - 9328 = 20072. Floored = 20072.00
        $result = $this->taxService->calculateGermanTax(70000, 0, false, 'single');
        // Soli: Tax is 20072. zvE is 70000. This is > 66761, so full Soli.
        // Soli = 20072 * 0.055 = 1103.96
        $this->assertTaxComponents($result, 20072.00, 1103.96, 0.00, 'Single, ZVE 70k');
    }

    public function testIncomeTax_Single_Linear45Zone_Example1()
    {
        // zvE = 300000. Tax = 0.45 * 300000 - 18307 = 135000 - 18307 = 116693. Floored = 116693.00
        $result = $this->taxService->calculateGermanTax(300000, 0, false, 'single');
        // Soli: Tax is 116693. zvE is 300000. Full Soli.
        // Soli = 116693 * 0.055 = 6418.115. Rounded = 6418.12
        $this->assertTaxComponents($result, 116693.00, 6418.12, 0.00, 'Single, ZVE 300k');
    }

    public function testIncomeTax_Married_Splitting_ProgressiveZone()
    {
        // Total zvE = 40000. zvE for calculation = 20000.
        // Tax on 20000 (single) = 3163.00 (from above). Doubled = 6326.00
        $result = $this->taxService->calculateGermanTax(40000, 0, false, 'married_joint');
        // Soli: Tax is 6326. zvE is 40000. Married Exemption ZVE = 36260. Married MilderZoneMax ZVE = 133522.
        // zvE 40000 is in married milder zone.
        // ReductionFactor = 1 - ((40000 - 36260) / (133522 - 36260)) = 1 - (3740 / 97262) = 1 - 0.038451... = 0.961548...
        // Soli = (6326 * 0.055) * 0.961548 = 347.93 * 0.961548 = 334.556... Rounded = 334.56
        $this->assertTaxComponents($result, 6326.00, 334.56, 0.00, 'Married, ZVE 40k');
    }

    public function testIncomeTax_Married_Splitting_Linear42Zone()
    {
        // Total zvE = 140000. zvE for calculation = 70000.
        // Tax on 70000 (single) = 20072.00. Doubled = 40144.00
        $result = $this->taxService->calculateGermanTax(140000, 0, false, 'married_joint');
        // Soli: Tax = 40144. zvE = 140000. Married MilderZoneMax ZVE = 133522.
        // Since zvE 140000 > 133522, full Soli applies.
        // Soli = 40144 * 0.055 = 2207.92
        $this->assertTaxComponents($result, 40144.00, 2207.92, 0.00, 'Married, ZVE 140k');
    }


    // --- SOLIDARITY SURCHARGE TESTS (more specific edge cases) ---
    public function testSoli_Single_JustAboveExemption_MilderZone()
    {
        // zvE = 19000. Income Tax: y=(19000-11604)/10000 = 0.7396. Tax=(212.02*0.7396+2400)*0.7396+1000 = (156.810792+2400)*0.7396+1000 = 2556.810792*0.7396+1000 = 1890.9994+1000 = 2890.9994. Floored = 2890.00
        $result = $this->taxService->calculateGermanTax(19000, 0, false, 'single');
        // Soli for Tax=2890, ZVE=19000: Exemption ZVE=18130. It is in milder zone.
        // ReductionFactor = 1 - ((19000 - 18130) / (66761 - 18130)) = 1 - (870 / 48631) = 1 - 0.017891... = 0.982108...
        // Soli = (2890 * 0.055) * 0.982108 = 158.95 * 0.982108 = 156.105... Rounded = 156.11
        // Note: Original prompt had 156.10, re-calculation with more precision suggests 156.11
        $this->assertTaxComponents($result, 2890.00, 156.11, 0.00, 'Soli Single Milder Zone Low');
    }

    public function testSoli_Single_NoSoli_ZveBelowExemption() {
        // zvE = 18000 (Income Tax will be calculated, but Soli should be 0 as zvE <= 18130)
        // Income Tax: y=(18000-11604)/10000 = 0.6396. Tax=(212.02*0.6396+2400)*0.6396+1000 = (135.610792+2400)*0.6396+1000 = 2535.610792*0.6396+1000 = 1621.7786+1000 = 2621.7786. Floored = 2621.00
        $result = $this->taxService->calculateGermanTax(18000, 0, false, 'single');
        $this->assertTaxComponents($result, 2621.00, 0.00, 0.00, 'Soli Single No Soli zvE below threshold');
    }

    public function testSoli_Single_ExactlyAtExemptionZve() {
        // zvE = 18130.
        // Income Tax: y=(18130-11604)/10000 = 0.6526. Tax=(212.02*0.6526+2400)*0.6526+1000 = (138.364252+2400)*0.6526+1000 = 2538.364252*0.6526+1000 = 1656.5389+1000 = 2656.5389. Floored = 2656.00
        $result = $this->taxService->calculateGermanTax(18130, 0, false, 'single');
        $this->assertTaxComponents($result, 2656.00, 0.00, 0.00, 'Soli Single Exactly at ZVE Exemption');
    }

    // --- CHURCH TAX TESTS ---
    public function testChurchTax_Member_DefaultRate()
    {
        // Use ZVE 60000 (Single), Income Tax = 17576.00, Soli = 134.39
        // Church Tax = 17576.00 * 0.09 (default) = 1581.84
        $result = $this->taxService->calculateGermanTax(60000, 0, true, 'single'); // state_abbr = null
        $this->assertTaxComponents($result, 17576.00, 134.39, 1581.84, 'Church Tax Default Rate');
    }

    public function testChurchTax_Member_BadenWuerttemberg()
    {
        // Use ZVE 60000 (Single), Income Tax = 17576.00, Soli = 134.39
        // Church Tax = 17576.00 * 0.08 (BW) = 1406.08
        $result = $this->taxService->calculateGermanTax(60000, 0, true, 'single', 'BW');
        $this->assertTaxComponents($result, 17576.00, 134.39, 1406.08, 'Church Tax BW Rate');
    }

    public function testChurchTax_Member_Bayern()
    {
        // Use ZVE 60000 (Single), Income Tax = 17576.00, Soli = 134.39
        // Church Tax = 17576.00 * 0.08 (BY) = 1406.08
        $result = $this->taxService->calculateGermanTax(60000, 0, true, 'single', 'BY');
        $this->assertTaxComponents($result, 17576.00, 134.39, 1406.08, 'Church Tax BY Rate');
    }

    public function testChurchTax_Member_OtherState()
    {
        // Use ZVE 60000 (Single), Income Tax = 17576.00, Soli = 134.39
        // Church Tax = 17576.00 * 0.09 (e.g. NW) = 1581.84
        $result = $this->taxService->calculateGermanTax(60000, 0, true, 'single', 'NW');
        $this->assertTaxComponents($result, 17576.00, 134.39, 1581.84, 'Church Tax Other State Rate');
    }

    public function testChurchTax_NotMember()
    {
        $result = $this->taxService->calculateGermanTax(60000, 0, false, 'single', 'NW');
        $this->assertTaxComponents($result, 17576.00, 134.39, 0.00, 'Church Tax Not Member');
    }

    // --- COMBINED TESTS ---
    public function testCombined_Married_WithExpenses_ChurchMember_Bavaria()
    {
        $annualGross = 80000;
        $businessExpenses = 10000;
        $zve = $annualGross - $businessExpenses; // 70000
        // Married, zvE = 70000. zvE for calc = 35000.
        // y = (35000-11604)/10000 = 2.3396
        // TaxSingleHalf = (212.02 * 2.3396 + 2400) * 2.3396 + 1000 = (496.043992 + 2400) * 2.3396 + 1000 = 2896.043992 * 2.3396 + 1000 = 6775.5833 + 1000 = 7775.5833. Floored = 7775.00
        // IncomeTaxMarried = 7775.00 * 2 = 15550.00

        // Soli: Tax = 15550. zvE = 70000. Married Exemption ZVE = 36260. Married MilderZoneMax ZVE = 133522.
        // zvE 70000 is in married milder zone.
        // ReductionFactor = 1 - ((70000 - 36260) / (133522 - 36260)) = 1 - (33740 / 97262) = 1 - 0.346909... = 0.653090...
        // Soli = (15550 * 0.055) * 0.653090 = 855.25 * 0.653090 = 558.500... Rounded = 558.50

        // Church Tax (Bavaria 8%): 15550.00 * 0.08 = 1244.00
        $result = $this->taxService->calculateGermanTax($annualGross, $businessExpenses, true, 'married_joint', 'BY');
        $this->assertEquals($zve, $result['calculations']['taxableIncomeZvE']);
        $this->assertTaxComponents($result, 15550.00, 558.50, 1244.00, 'Combined Married Full Case');
    }
}
