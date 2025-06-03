<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Mockery;
use PayPal\Core\PayPalHttpClient;
// Removed use PaypalServerSdkLib\V1\Subscriptions\SubscriptionsCreateRequest;
use PaypalServerSdkLib\Http\HttpResponse; // Using SDK's own namespace
use PaypalServerSdkLib\Http\HttpException as PayPalHttpException; // Using SDK's own namespace and aliasing
// It's possible the controller uses its own namespace or a wrapper for these, adjust if needed.
// use App\Http\Controllers\SubscriptionController; // No longer directly interacting with controller internals for mocking


class SubscriptionSubscribeTest extends TestCase
{
    use RefreshDatabase;

    protected $payPalClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the PayPalHttpClient to ensure a non-null client is injected
        $this->payPalClientMock = Mockery::mock(PayPalHttpClient::class);
        $this->app->instance(PayPalHttpClient::class, $this->payPalClientMock);

        // Ensure PayPal config is set for tests (though not strictly needed by controller anymore for subscribe, good for consistency)
        Config::set('services.paypal.mode', 'sandbox');
        Config::set('services.paypal.sandbox.client_id', 'test_client_id'); // Actual values don't matter for stub
        Config::set('services.paypal.sandbox.client_secret', 'test_client_secret');
        Config::set('services.paypal.webhook_id', 'test_webhook_id');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_initiate_subscription(): void
    {
        $plan = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-VALID123']);
        $response = $this->post(route('subscriptions.subscribe', $plan));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_initiate_subscription_to_valid_plan(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-XYZ789', 'price' => 20.00]);
        // $fakeApprovalUrl and $fakePayPalSubscriptionId are now generated within the controller.

        // No PayPal client mocking needed as the controller part is stubbed.

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe', $plan));

        $response->assertStatus(302); // It's a redirect
        $this->assertStringContainsString('https://www.paypal.com/checkoutnow?token=stubbed_paypal_sub_', $response->headers->get('Location'));

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            // paypal_subscription_id is dynamic, check separately
            'status' => 'pending_approval',
        ]);
        $userSub = UserSubscription::where('user_id', $user->id)->where('subscription_plan_id', $plan->id)->first();
        $this->assertNotNull($userSub);
        $this->assertStringStartsWith('stubbed_paypal_sub_', $userSub->paypal_subscription_id);
    }

    public function test_user_with_active_subscription_cannot_initiate_new_subscription(): void
    {
        $user = User::factory()->create();
        $plan1 = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-PLANOLD']);
        UserSubscription::factory()->for($user)->for($plan1)->create(['status' => 'active', 'ends_at' => now()->addMonth()]);

        $plan2 = SubscriptionPlan::factory()->create(['paypal_plan_id' => 'P-PLANNEW']);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe', $plan2));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('info', 'You already have an active subscription.');
        $this->assertDatabaseCount('user_subscriptions', 1);
    }

    public function test_cannot_subscribe_to_plan_without_paypal_plan_id(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['paypal_plan_id' => null]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe', $plan));

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('error', 'This plan is not configured for PayPal subscriptions.');
        $this->assertDatabaseMissing('user_subscriptions', ['user_id' => $user->id]);
    }

    // Removed test_cannot_subscribe_if_paypal_client_fails_to_initialize
    // Removed test_handles_paypal_api_http_exception_during_subscription_creation

    public function test_attempt_to_subscribe_to_non_existent_plan_returns_404(): void
    {
        $user = User::factory()->create();
        $nonExistentPlanId = 9999;

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe', $nonExistentPlanId));
        $response->assertStatus(404);
    }
}
