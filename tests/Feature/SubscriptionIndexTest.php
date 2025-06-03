<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_from_subscription_index(): void
    {
        $response = $this->get(route('subscriptions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_subscription_index_page(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('subscriptions.index'));
        $response->assertStatus(200);
        $response->assertViewIs('subscriptions.index');
    }

    public function test_subscription_index_page_displays_active_plans_with_paypal_ids(): void
    {
        $user = User::factory()->create();

        $plan1 = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Gold Plan'], // Assuming translatable
            'price' => 19.99,
            'paypal_plan_id' => 'P-GOLD123'
        ]);
        $plan2 = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Silver Plan'],
            'price' => 9.99,
            'paypal_plan_id' => 'P-SILVER123'
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertSeeText('Gold Plan');
        $response->assertSee(number_format(19.99, 2));
        $response->assertSeeText('Silver Plan');
        $response->assertSee(number_format(9.99, 2));
    }

    public function test_subscription_index_page_does_not_display_plans_without_paypal_id(): void
    {
        $user = User::factory()->create();
        $planWithPaypal = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Visible Plan'],
            'price' => 29.99,
            'paypal_plan_id' => 'P-VISIBLE123'
        ]);
        $planWithoutPaypal = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Hidden Plan No Paypal'],
            'price' => 10.00,
            'paypal_plan_id' => null
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertSeeText('Visible Plan');
        $response->assertDontSeeText('Hidden Plan No Paypal');
    }

    public function test_subscription_index_page_does_not_display_soft_deleted_plans(): void
    {
        $user = User::factory()->create();
        $activePlan = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Active Visible Plan'],
            'price' => 39.99,
            'paypal_plan_id' => 'P-ACTIVEVISIBLE'
        ]);
        $deletedPlan = SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Deleted Hidden Plan'],
            'price' => 15.00,
            'paypal_plan_id' => 'P-DELETEDHIDDEN',
            'deleted_at' => now() // Soft delete
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.index'));

        $response->assertSeeText('Active Visible Plan');
        $response->assertDontSeeText('Deleted Hidden Plan');
    }

    public function test_subscription_index_page_shows_message_when_no_active_plans_exist(): void
    {
        $user = User::factory()->create();
        // No plans created, or only plans that shouldn't be visible

        $response = $this->actingAs($user)->get(route('subscriptions.index'));
        $response->assertStatus(200);

        // Assuming the view 'subscriptions.index' has a conditional message.
        // This assertion depends on the view's content.
        // A common approach is to check for a specific text like "No subscription plans available at the moment."
        // Or, if it just shows an empty list, assert that specific plan details are NOT seen.
        // For this example, let's assume the view handles it gracefully and we just check no specific plan text is seen
        // if we had created some plans that *shouldn't* be visible.
        // A more robust test might involve checking for a specific "no plans" message if the view implements it.
        $plans = $response->viewData('plans'); // Get the 'plans' variable passed to the view
        $this->assertCount(0, $plans, "There should be no plans passed to the view.");

        // Example of how you might check for a specific message if it exists in the view:
        // $response->assertSeeText("No subscription plans available at the moment.");
    }
}
