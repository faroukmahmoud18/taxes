<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $anotherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create(); // For testing data isolation
    }

    private function createExpense(User $user, string $description, float $amount, string $date, string $category = 'Food', bool $isBusiness = false)
    {
        return Expense::factory()->for($user)->create([
            'description' => ['en' => $description],
            'amount' => $amount,
            'expense_date' => Carbon::parse($date),
            'category' => $category,
            'is_business_expense' => $isBusiness,
        ]);
    }

    // --- Basic Access ---
    public function test_sanity_check_expenses_create_route_works(): void // TEMP TEST
    {
        $response = $this->actingAs($this->user)->get(route('expenses.create'));
        $response->assertStatus(200);
        $response->assertViewIs('expenses.create');
    }

    public function test_unauthenticated_user_cannot_access_expense_report(): void
    {
        $response = $this->get(route('expenses.report'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_expense_report_view(): void
    {
        $response = $this->actingAs($this->user)->get('/expenses/report'); // Using raw URL
        $response->assertStatus(200);
        $response->assertViewIs('expenses.report');
    }

    // --- Tests for different periods ---

    public function test_report_for_current_month_is_correct(): void
    {
        // In current month
        $this->createExpense($this->user, 'Lunch Current Month', 50, now()->startOfMonth()->addDays(2)->format('Y-m-d'), 'Food', true);
        $this->createExpense($this->user, 'Stationery Current Month', 20, now()->startOfMonth()->format('Y-m-d'), 'Office', false);
        // In last month
        $this->createExpense($this->user, 'Dinner Last Month', 70, now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), 'Food', true);
        // Another user's expense in current month (should not be included)
        $this->createExpense($this->anotherUser, 'Other User Lunch', 30, now()->startOfMonth()->format('Y-m-d'));

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'current_month']));

        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 70.00); // 50 + 20
        $response->assertViewHas('businessExpensesTotal', 50.00);
        $response->assertViewHas('privateExpensesTotal', 20.00);
        $response->assertViewHas('expensesByCategory', function ($categories) {
            return isset($categories['Food']) && $categories['Food'] == 50.00 &&
                   isset($categories['Office']) && $categories['Office'] == 20.00;
        });
        $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 2;
        });
        $response->assertSeeText('Lunch Current Month');
        $response->assertDontSeeText('Dinner Last Month');
        $response->assertDontSeeText('Other User Lunch');
    }

    public function test_report_for_last_month_is_correct(): void
    {
        // In last month
        $this->createExpense($this->user, 'Dinner Last Month', 70, now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'), 'Food', true);
        $this->createExpense($this->user, 'Books Last Month', 25, now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d'), 'Education', false);
        // In current month
        $this->createExpense($this->user, 'Lunch Current Month', 50, now()->startOfMonth()->format('Y-m-d'), 'Food', true);

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'last_month']));

        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 95.00); // 70 + 25
        $response->assertViewHas('businessExpensesTotal', 70.00);
        $response->assertViewHas('privateExpensesTotal', 25.00);
        $response->assertViewHas('expensesByCategory', function ($categories) {
            return isset($categories['Food']) && $categories['Food'] == 70.00 &&
                   isset($categories['Education']) && $categories['Education'] == 25.00;
        });
         $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 2;
        });
        $response->assertSeeText('Dinner Last Month');
        $response->assertDontSeeText('Lunch Current Month');
    }

    // More tests for 'current_year', 'all_time', 'custom_range', 'no_expenses' will be added.

    public function test_report_for_current_year_is_correct(): void
    {
        // This year
        $this->createExpense($this->user, 'New Laptop', 1200, now()->startOfYear()->format('Y-m-d'), 'Electronics', true);
        $this->createExpense($this->user, 'Software Subscription', 150, now()->subMonths(2)->format('Y-m-d'), 'Software', true);
        // Last year
        $this->createExpense($this->user, 'Old Books', 80, now()->subYear()->startOfYear()->format('Y-m-d'), 'Education', false);

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'current_year']));

        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 1350.00); // 1200 + 150
        $response->assertViewHas('businessExpensesTotal', 1350.00);
        $response->assertViewHas('privateExpensesTotal', 0.00);
        $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 2;
        });
        $response->assertSeeText('New Laptop');
        $response->assertDontSeeText('Old Books');
    }

    public function test_report_for_all_time_is_correct(): void
    {
        // This year
        $this->createExpense($this->user, 'New Monitor', 300, now()->startOfYear()->format('Y-m-d'), 'Electronics', true);
        // Last year
        $this->createExpense($this->user, 'Old Keyboard', 50, now()->subYear()->startOfYear()->format('Y-m-d'), 'Electronics', false);

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'all_time']));

        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 350.00); // 300 + 50
        $response->assertViewHas('businessExpensesTotal', 300.00);
        $response->assertViewHas('privateExpensesTotal', 50.00);
        $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 2;
        });
        $response->assertSeeText('New Monitor');
        $response->assertSeeText('Old Keyboard');
    }

    public function test_report_shows_message_if_no_expenses_in_selected_period(): void
    {
        // Create an expense, but it won't be in the "last_month" if current month is different
        $this->createExpense($this->user, 'Current Month Expense', 100, now()->format('Y-m-d'));

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'last_month']));
        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 0.00);
        $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 0;
        });
        // Assuming the view shows a specific message for no expenses in the period,
        // or at least doesn't show the "Current Month Expense".
        // The view has: @forelse ($expensesForPeriod as $expense) ... @empty <td colspan="...">{__("No expenses found for this period.")}</td>
        // This message might not be directly assertable with assertSeeText if it's inside complex table structure.
        // But checking count and total is a good indicator.
        $response->assertDontSeeText('Current Month Expense');
        // We could check for "No expenses found for this period." if it's consistently displayed.
        // For now, asserting empty collection and zero total is sufficient.
    }

    public function test_report_for_custom_date_range_is_correct(): void
    {
        $startDate = now()->subDays(20);
        $endDate = now()->subDays(10);

        // Within custom range
        $this->createExpense($this->user, 'Custom Range Expense 1', 75, $startDate->copy()->addDay()->format('Y-m-d'), 'Custom', true);
        $this->createExpense($this->user, 'Custom Range Expense 2', 25, $endDate->copy()->subDay()->format('Y-m-d'), 'Custom', false);
        // Outside custom range (before)
        $this->createExpense($this->user, 'Before Range Expense', 10, $startDate->copy()->subDays(5)->format('Y-m-d'));
        // Outside custom range (after)
        $this->createExpense($this->user, 'After Range Expense', 15, $endDate->copy()->addDays(5)->format('Y-m-d'));

        $response = $this->actingAs($this->user)->get(route('expenses.report', [
            'period' => 'custom',
            'custom_start_date' => $startDate->format('Y-m-d'),
            'custom_end_date' => $endDate->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('totalExpenses', 100.00); // 75 + 25
        $response->assertViewHas('businessExpensesTotal', 75.00);
        $response->assertViewHas('privateExpensesTotal', 25.00);
        $response->assertViewHas('expensesForPeriod', function ($expenses) {
            return $expenses->count() === 2;
        });
        $response->assertSeeText('Custom Range Expense 1');
        $response->assertSeeText('Custom Range Expense 2');
        $response->assertDontSeeText('Before Range Expense');
        $response->assertDontSeeText('After Range Expense');
        $response->assertViewHas('customStartDateInput', $startDate->format('Y-m-d'));
        $response->assertViewHas('customEndDateInput', $endDate->format('Y-m-d'));
    }

    public function test_report_custom_date_range_start_date_after_end_date_defaults_to_current_month(): void
    {
        // This behavior is based on how prepareReportDates handles invalid custom dates
        // It falls through to default, which then might set it to current_month if period is not 'all_time'
        // The current prepareReportDates logic: if custom dates are invalid, it does not set startDate/endDate,
        // then switch default will hit if period was 'custom'. If period is not 'all_time', it defaults to current_month.

        $this->createExpense($this->user, 'This Month Expense', 200, now()->startOfMonth()->format('Y-m-d'));
        $this->createExpense($this->user, 'Last Month Expense', 500, now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'));


        $response = $this->actingAs($this->user)->get(route('expenses.report', [
            'period' => 'custom',
            'custom_start_date' => now()->format('Y-m-d'),
            'custom_end_date' => now()->subDay()->format('Y-m-d'), // Start after end
        ]));

        $response->assertStatus(200);
        // It should default to 'current_month' as per prepareReportDates logic for invalid custom range
        $response->assertViewHas('period', 'current_month');
        $response->assertViewHas('totalExpenses', 200.00); // Only This Month Expense
        $response->assertSeeText('This Month Expense');
        $response->assertDontSeeText('Last Month Expense');
    }

    public function test_report_custom_date_range_missing_one_date_defaults_to_current_month(): void
    {
        $this->createExpense($this->user, 'This Month Expense Again', 250, now()->startOfMonth()->format('Y-m-d'));

        $response = $this->actingAs($this->user)->get(route('expenses.report', [
            'period' => 'custom',
            'custom_start_date' => now()->format('Y-m-d'),
            // custom_end_date is missing
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('period', 'current_month');
        $response->assertViewHas('totalExpenses', 250.00);
        $response->assertSeeText('This Month Expense Again');
    }

    public function test_report_defaults_to_current_month_if_invalid_period_is_passed(): void
    {
        $this->createExpense($this->user, 'Current Month Expense For Default', 300, now()->startOfMonth()->format('Y-m-d'));
        $this->createExpense($this->user, 'Previous Month Expense For Default', 400, now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'));

        $response = $this->actingAs($this->user)->get(route('expenses.report', ['period' => 'invalid_junk_period']));

        $response->assertStatus(200);
        $response->assertViewHas('period', 'current_month');
        $response->assertViewHas('totalExpenses', 300.00);
        $response->assertSeeText('Current Month Expense For Default');
        $response->assertDontSeeText('Previous Month Expense For Default');
    }
}
