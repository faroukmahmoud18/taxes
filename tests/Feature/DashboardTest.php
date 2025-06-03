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

    public function test_dashboard_displays_details_for_active_subscription_including_status_and_manage_link(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => ['en' => 'Gold Active Plan']]);
        $activeSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active',
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSeeText('Gold Active Plan');
        // Note: assertSeeText will escape HTML, so we search for the text content.
        // If precise HTML structure with tags is needed, a different assertion or DOM crawler might be better.
        // For capitalize, CSS handles it, source is 'active'.
        $response->assertSee(__('Status:') . ' <span class="font-semibold capitalize">active</span>', false);
        $response->assertSeeText(__('Next Billing Date:') . ' ' . $activeSubscription->ends_at->toFormattedDateString(), false);
        $response->assertSee(route('subscriptions.index')); // Assuming "Manage Subscription" links here
        $response->assertSeeText(__('Manage Subscription'));
    }

    // REVISED test_dashboard_displays_details_for_cancelled_subscription_that_is_not_yet_expired
    public function test_dashboard_shows_no_active_subscription_for_cancelled_but_future_ends_at_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => ['en' => 'Cancelled Plan Future End']]);
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'cancelled', // Not 'active'
            'ends_at' => Carbon::now()->addDays(15),
            'cancelled_at' => Carbon::now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSeeText(__('You are not currently subscribed to any plan.'));
        $response->assertSeeText(__('View Plans'));
        $response->assertDontSeeText('Cancelled Plan Future End');
    }

    public function test_dashboard_displays_message_for_expired_subscription_as_no_active_subscription(): void
    {
        // This scenario should effectively be like having no active subscription,
        // as DashboardController fetches the *active* one.
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['name' => ['en' => 'Old Expired Plan']]);
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'expired',
            'ends_at' => Carbon::now()->subDays(1), // Expired yesterday
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSeeText(__('You are not currently subscribed to any plan.'));
        $response->assertSeeText(__('View Plans'));
        $response->assertDontSeeText('Old Expired Plan'); // Should not show details of the expired plan
    }
}
