<?php

namespace Tests\Unit;

use App\Http\Controllers\ExpenseController;
use App\Models\User;
use App\Models\Expense; // Added Expense model import
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth; // To mock Auth facade
use Tests\TestCase;
use ReflectionMethod;
use Illuminate\Foundation\Testing\RefreshDatabase; // Added for creating persisted users

class ExpenseControllerLogicTest extends TestCase
{
    use RefreshDatabase; // Added for creating persisted users

    protected ExpenseController $controller;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->controller = new ExpenseController(); // If controller has no dependencies
        $this->controller = $this->app->make(ExpenseController::class); // Resolve from container

        $this->user = User::factory()->create(); // Create a persisted user

        $this->actingAs($this->user); // Set the user for Auth facade
    }

    protected function callPrepareReportDates(array $requestData): array
    {
        $request = new Request($requestData);
        $startDate = null;
        // Set a known 'now' for endDate default to make tests predictable
        $knownNow = isset($requestData['test_now']) ? Carbon::parse($requestData['test_now']) : Carbon::now();
        $endDate = $knownNow->copy()->endOfDay();

        $period = $requestData['period'] ?? 'current_month';
        // If period is custom and dates are provided, prepareReportDates should use them.
        // If period is custom and dates are NOT provided, it defaults to current_month.
        // The controller's report method sets $period based on input first.
        // If custom dates are in $requestData, $period will be set to 'custom' by prepareReportDates.
        if (isset($requestData['custom_start_date']) && isset($requestData['custom_end_date'])) {
            // No explicit period needed if custom dates are set, method updates it to 'custom'
        }


        // Use ReflectionMethod to make prepareReportDates accessible
        $method = new ReflectionMethod(ExpenseController::class, 'prepareReportDates');
        $method->setAccessible(true);

        // The method modifies $startDate, $endDate, $period by reference
        $method->invokeArgs($this->controller, [$request, &$startDate, &$endDate, &$period]);

        return ['startDate' => $startDate, 'endDate' => $endDate, 'period' => $period];
    }

    public function test_prepare_report_dates_for_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0)); // Freeze time for consistent testing

        $result = $this->callPrepareReportDates(['period' => 'current_month', 'test_now' => now()]);

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);

        Carbon::setTestNow(); // Clear frozen time
    }

    public function test_prepare_report_dates_for_last_month(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));

        $result = $this->callPrepareReportDates(['period' => 'last_month', 'test_now' => now()]);

        $this->assertEquals(Carbon::create(2024, 6, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 6, 30)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('last_month', $result['period']);

        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_for_current_year(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));

        $result = $this->callPrepareReportDates(['period' => 'current_year', 'test_now' => now()]);

        $this->assertEquals(Carbon::create(2024, 1, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 12, 31)->endOfYear()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_year', $result['period']);

        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_for_all_time(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        $expectedEndDate = Carbon::now()->endOfDay();

        $result = $this->callPrepareReportDates(['period' => 'all_time', 'test_now' => now()]);

        $this->assertNull($result['startDate']); // Start date is null for all_time
        $this->assertEquals($expectedEndDate, $result['endDate']);
        $this->assertEquals('all_time', $result['period']);

        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_for_valid_custom_range(): void
    {
        $customStart = '2024-03-10';
        $customEnd = '2024-04-20';

        // Pass 'test_now' to simulate a 'now' that doesn't interfere with custom dates
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));


        $result = $this->callPrepareReportDates([
            // period will be set to 'custom' by the method if custom dates are valid
            'custom_start_date' => $customStart,
            'custom_end_date' => $customEnd,
            'test_now' => now() // Represents current time for endDate default if custom fails
        ]);

        $this->assertEquals(Carbon::parse($customStart)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::parse($customEnd)->endOfDay(), $result['endDate']);
        $this->assertEquals('custom', $result['period']);
        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_defaults_to_current_month_for_invalid_period(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        $result = $this->callPrepareReportDates(['period' => 'invalid_period_string', 'test_now' => now()]);

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);
        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_custom_range_start_after_end_defaults_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        $result = $this->callPrepareReportDates([
            'period' => 'custom', // Explicitly setting period to custom to trigger custom date logic first
            'custom_start_date' => '2024-05-01',
            'custom_end_date' => '2024-04-01', // Start after end
            'test_now' => now()
        ]);

        // The method's logic: if custom dates are invalid, it falls into the default case of the switch.
        // In that default case, if period was 'custom' initially (and not 'all_time'), it resets to 'current_month'.
        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);
        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_custom_range_missing_end_date_defaults_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        $result = $this->callPrepareReportDates([
            'period' => 'custom',
            'custom_start_date' => '2024-07-01',
            // custom_end_date is missing
            'test_now' => now()
        ]);

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);
        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_custom_range_missing_start_date_defaults_to_current_month(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        $result = $this->callPrepareReportDates([
            'period' => 'custom',
            'custom_end_date' => '2024-07-20',
            // custom_start_date is missing
            'test_now' => now()
        ]);

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);
        Carbon::setTestNow();
    }

    public function test_prepare_report_dates_default_period_is_current_month_if_period_not_in_request(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 7, 15, 12, 0, 0));
        // 'period' key is omitted from $requestData
        $result = $this->callPrepareReportDates(['test_now' => now()]);

        $this->assertEquals(Carbon::create(2024, 7, 1)->startOfDay(), $result['startDate']);
        $this->assertEquals(Carbon::create(2024, 7, 31)->endOfMonth()->endOfDay(), $result['endDate']);
        $this->assertEquals('current_month', $result['period']);
        Carbon::setTestNow();
    }

    public function test_report_method_aggregates_data_correctly_for_current_month(): void
    {
        // User for this test (created in setUp)
        $testUser = $this->user;

        Carbon::setTestNow(Carbon::create(2024, 7, 15));

        // Expenses for the user
        Expense::factory()->for($testUser)->create(['amount' => 100, 'expense_date' => now(), 'category' => 'Food', 'is_business_expense' => true]);
        Expense::factory()->for($testUser)->create(['amount' => 50, 'expense_date' => now()->subDay(), 'category' => 'Food', 'is_business_expense' => false]);
        Expense::factory()->for($testUser)->create(['amount' => 75, 'expense_date' => now()->startOfMonth(), 'category' => 'Transport', 'is_business_expense' => true]);
        // Expense from last month (should not be included)
        Expense::factory()->for($testUser)->create(['amount' => 200, 'expense_date' => now()->subMonth(), 'category' => 'Other']);
        // Expense for another user (should not be included)
        $anotherUser = User::factory()->create();
        Expense::factory()->for($anotherUser)->create(['amount' => 1000, 'expense_date' => now()]);


        $request = new Request(['period' => 'current_month']);

        $view = $this->controller->report($request);

        $this->assertEquals('expenses.report', $view->getName());
        $viewData = $view->getData();

        $this->assertEquals(225.00, $viewData['totalExpenses']); // 100 + 50 + 75
        $this->assertEquals(175.00, $viewData['businessExpensesTotal']); // 100 + 75
        $this->assertEquals(50.00, $viewData['privateExpensesTotal']);
        $this->assertCount(2, $viewData['expensesByCategory']);
        $this->assertEquals(150.00, $viewData['expensesByCategory']['Food']);
        $this->assertEquals(75.00, $viewData['expensesByCategory']['Transport']);
        $this->assertCount(3, $viewData['expensesForPeriod']); // 3 expenses in current month
        $this->assertEquals('current_month', $viewData['period']);

        Carbon::setTestNow();
    }

    public function test_report_method_handles_no_expenses_in_period_correctly(): void
    {
        $testUser = $this->user;
        Carbon::setTestNow(Carbon::create(2024, 7, 15));

        // No expenses created for this user in this period

        $request = new Request(['period' => 'current_month']);
        $view = $this->controller->report($request);
        $viewData = $view->getData();

        $this->assertEquals(0.00, $viewData['totalExpenses']);
        $this->assertEquals(0.00, $viewData['businessExpensesTotal']);
        $this->assertEquals(0.00, $viewData['privateExpensesTotal']);
        $this->assertCount(0, $viewData['expensesByCategory']);
        $this->assertCount(0, $viewData['expensesForPeriod']);
        $this->assertEquals('current_month', $viewData['period']);

        Carbon::setTestNow();
    }
}
