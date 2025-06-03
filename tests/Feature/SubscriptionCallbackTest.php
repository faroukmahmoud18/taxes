<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SubscriptionCallbackTest extends TestCase
{
    use RefreshDatabase;

    // --- Tests for subscriptions.success ---

    public function test_success_callback_redirects_unauthenticated_user(): void
    {
        $response = $this->get(route('subscriptions.success'));
        $response->assertRedirect(route('login'));
    }

    public function test_success_callback_missing_subscription_id_redirects_with_error(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('subscriptions.success')); // No subscription_id query param

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('error', 'Subscription confirmation from PayPal is missing an ID. Please check your dashboard or contact support.');
    }

    public function test_success_callback_updates_status_for_pending_approval_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubId = 'I-TESTSUCCESS123';
        $subscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($user)
                         ->get(route('subscriptions.success', ['subscription_id' => $paypalSubId, 'token' => 'test_token']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Thank you! Your subscription with PayPal is approved and will be activated shortly.');

        $subscription->refresh();
        $this->assertEquals('pending_webhook_confirmation', $subscription->status);
    }

    public function test_success_callback_does_not_change_status_if_not_pending_approval(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubId = 'I-TESTALREADYACTIVE';
        $initialStatus = 'active'; // or 'pending_webhook_confirmation'
        $subscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubId,
            'status' => $initialStatus,
        ]);

        $response = $this->actingAs($user)
                         ->get(route('subscriptions.success', ['subscription_id' => $paypalSubId, 'token' => 'test_token']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success'); // Still shows general success

        $subscription->refresh();
        $this->assertEquals($initialStatus, $subscription->status); // Status should remain unchanged
    }

    public function test_success_callback_no_matching_local_subscription_redirects_with_warning(): void
    {
        $user = User::factory()->create();
        $paypalSubId = 'I-TESTNOMATCH';

        $response = $this->actingAs($user)
                         ->get(route('subscriptions.success', ['subscription_id' => $paypalSubId, 'token' => 'test_token']));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('warning', 'Your PayPal transaction was successful, but we encountered an issue linking it to your account. Please contact support.');
    }

    // --- Tests for subscriptions.cancel ---

    public function test_cancel_callback_redirects_unauthenticated_user(): void
    {
        $response = $this->get(route('subscriptions.cancel'));
        $response->assertRedirect(route('login'));
    }

    public function test_cancel_callback_updates_status_for_pending_approval_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $subscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'pending_approval',
            'ends_at' => null, // ends_at might be null before this point
        ]);

        Carbon::setTestNow(now()); // Freeze time for ends_at comparison

        $response = $this->actingAs($user)->get(route('subscriptions.cancel'));

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('info', 'You have cancelled the subscription process.');

        $subscription->refresh();
        $this->assertEquals('cancelled_by_user_at_paypal', $subscription->status);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->isSameSecond(Carbon::getTestNow()), "ends_at {$subscription->ends_at} should be the same second as " . Carbon::getTestNow());

        Carbon::setTestNow(); // Clear frozen time
    }

    public function test_cancel_callback_does_not_affect_non_pending_approval_subscriptions(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $activeSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active', // Not 'pending_approval'
            'ends_at' => now()->addMonth(),
        ]);
        $originalEndsAt = $activeSubscription->ends_at;

        $response = $this->actingAs($user)->get(route('subscriptions.cancel'));

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('info', 'You have cancelled the subscription process.');

        $activeSubscription->refresh();
        $this->assertEquals('active', $activeSubscription->status);
        $this->assertTrue($originalEndsAt->equalTo($activeSubscription->ends_at)); // ends_at should be unchanged
    }

    public function test_cancel_callback_no_pending_approval_subscription_still_redirects_with_info(): void
    {
        $user = User::factory()->create();
        // No subscriptions created for this user, or none are 'pending_approval'

        $response = $this->actingAs($user)->get(route('subscriptions.cancel'));

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('info', 'You have cancelled the subscription process.');
    }
}
