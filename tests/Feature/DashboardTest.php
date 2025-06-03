<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Expense;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_successfully_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSeeText($user->name); // Simplified assertion
        $response->assertSeeText(__('My Subscription'));
        $response->assertSeeText(__('Recent Expenses'));
        $response->assertSeeText(__('Quick Actions'));
    }

    public function test_dashboard_displays_quick_action_links(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSeeText(__('Add New Expense'));
        $response->assertSee(route('expenses.create'));

        $response->assertSeeText(__('View Expense Report'));
        $response->assertSee(route('expenses.report'));

        $response->assertSeeText(__('Estimate Taxes'));
        $response->assertSee(route('tax-estimation.show'));
    }

    public function test_dashboard_displays_user_specific_data(): void
    {
        $user = User::factory()->create();

        // Create a subscription plan
        $plan = SubscriptionPlan::factory()->create(['price' => 10.00, 'name' => 'Monthly Gold']);

        // Create an active subscription for the user
        $subscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active',
            'starts_at' => Carbon::now()->subMonth(),
            'ends_at' => Carbon::now()->addMonth(),
            'paypal_subscription_id' => 'TESTSUB123',
        ]);

        // Create a recent expense for the user
        $expense = Expense::factory()->for($user)->create([
            'description' => 'Lunch Meeting Client X',
            'amount' => 25.50,
            'expense_date' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);

        // Check for subscription data
        $response->assertSeeText($plan->name);
        $response->assertSeeText($subscription->ends_at->toFormattedDateString());

        // Check for expense data
        $response->assertSeeText($expense->description);
        $response->assertSee(number_format($expense->amount, 2));

        // Check for total expenses this month (which includes the created expense)
        $response->assertSeeText(__('Total This Month:') . ' €' . number_format($expense->amount, 2));
    }

    public function test_dashboard_shows_view_plans_if_no_active_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSeeText(__('You are not currently subscribed to any plan.'));
        $response->assertSeeText(__('View Plans'));
        $response->assertSee(route('subscriptions.index'));
    }

    public function test_dashboard_shows_no_expenses_message_if_none_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSeeText(__('No expenses recorded recently.'));
        // Also check that total this month is 0.00
        $response->assertSeeText(__('Total This Month:') . ' €' . number_format(0, 2));
    }
}
