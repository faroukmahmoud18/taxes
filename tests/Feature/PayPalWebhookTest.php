<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayPalWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function getFixture(string $fileName): array
    {
        $path = base_path("tests/Fixtures/PayPalWebhooks/{$fileName}.json");
        if (!file_exists($path)) {
            throw new \Exception("Fixture file not found: {$path}");
        }
        $content = file_get_contents($path);
        return json_decode($content, true);
    }

    protected function simulateWebhook(string $eventType, array $resourceData, ?string $fixtureName = null): \Illuminate\Testing\TestResponse
    {
        $payload = [
            'id' => 'EVT-' . uniqid(),
            'event_version' => '1.0',
            'create_time' => Carbon::now()->toIso8601String(),
            'resource_type' => 'subscription', // Or other relevant type
            'event_type' => $eventType,
            'summary' => "Test event for {$eventType}",
            'resource' => $resourceData,
        ];

        if ($fixtureName) {
            $payload = $this->getFixture($fixtureName);
            // Allow overriding parts of the fixture if needed, e.g. resource.id
            if (isset($resourceData['id'])) { // If we pass an ID, override fixture's resource ID
                $payload['resource']['id'] = $resourceData['id'];
            }
             $payload['event_type'] = $eventType; // Ensure event type matches
        }

        // The conceptual signature verification in PayPalWebhookController logs a warning if webhook_id is not set.
        // Set it to avoid this specific log entry clouding test logs, focusing on event processing.
        config(['services.paypal.webhook_id' => 'test_webhook_id_from_config']);

        return $this->postJson(route('paypal.webhook'), $payload, ['Content-Type' => 'application/json']);
    }

    // --- Tests will go here ---

    public function test_webhook_returns_error_if_payload_is_missing_event_type(): void
    {
        $response = $this->postJson(route('paypal.webhook'), ['resource' => ['id' => 'foo']]);
        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error'); // Corrected assertion
    }

    public function test_webhook_returns_error_if_payload_is_missing_resource(): void
    {
        $response = $this->postJson(route('paypal.webhook'), ['event_type' => 'SOME.EVENT']);
        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error'); // Corrected assertion
    }

    public function test_webhook_handles_unrecognised_event_type_gracefully(): void
    {
        $response = $this->simulateWebhook('UNHANDLED.EVENT.TYPE', ['id' => 'some_id']);
        $response->assertStatus(200); // Should still acknowledge receipt
        $response->assertJson(['status' => 'success']); // And report success
    }

    public function test_billing_subscription_activated_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        // Create a local subscription that is pending, matching the PayPal ID in the fixture
        $paypalSubIdFromFixture = 'I-PAYPALSUBID123';
        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubIdFromFixture,
            'status' => 'pending_webhook_confirmation', // Or 'pending_approval'
            'ends_at' => null, // Explicitly null before activation
        ]);

        $fixtureData = $this->getFixture('billing_subscription_activated');
        $expectedNextBillingTime = Carbon::parse($fixtureData['resource']['billing_info']['next_billing_time']);
        $expectedStartTime = Carbon::parse($fixtureData['resource']['start_time']);

        $logSpy = Log::spy();

        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.ACTIVATED',
            ['id' => $paypalSubIdFromFixture], // Pass the ID to ensure fixture's resource.id is used
            'billing_subscription_activated' // Use the fixture
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('active', $localSubscription->status);
        $this->assertTrue($expectedStartTime->equalTo($localSubscription->starts_at));
        $this->assertTrue($expectedNextBillingTime->equalTo($localSubscription->ends_at));
        $this->assertNotNull($localSubscription->paypal_payload['event_activated']);

        $logSpy->shouldHaveReceived('warning')->withArgs(fn($message) => str_contains($message, 'Conceptual signature verification'))->once();
        $logSpy->shouldHaveReceived('info')->withArgs(fn ($message) => str_contains($message, "Subscription {$paypalSubIdFromFixture} ACTIVATED"))->once();
    }

    public function test_billing_subscription_activated_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-DOESNOTEXIST';

        $logSpy = Log::spy();

        $minimalResourceData = [
            'id' => $nonExistentPayPalSubId,
            'start_time' => Carbon::now()->toIso8601String(), // Dummy value
            'billing_info' => ['next_billing_time' => Carbon::now()->addMonth()->toIso8601String()] // Dummy value
        ];
        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.ACTIVATED',
            $minimalResourceData // Use an ID that won't match, but include expected keys
        );
        // The simulateWebhook will use this $minimalResourceData as the 'resource'

        $response->assertStatus(200); // Controller should still return success to PayPal
        $response->assertJson(['status' => 'success']);

        $logSpy->shouldHaveReceived('warning')->withArgs(fn($message) => str_contains($message, 'Conceptual signature verification'))->once();
        $logSpy->shouldHaveReceived('warning')->with("Webhook 'BILLING.SUBSCRIPTION.ACTIVATED': No local subscription found for PayPal ID: {$nonExistentPayPalSubId}.")->once();
    }

    public function test_billing_subscription_cancelled_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubIdFromFixture = 'I-PAYPALSUBID456'; // Matches ID in new fixture

        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubIdFromFixture,
            'status' => 'active', // Assuming it was active before cancellation
            'ends_at' => Carbon::now()->addMonth(),
            'cancelled_at' => null, // Explicitly null before cancellation
        ]);

        $fixtureData = $this->getFixture('billing_subscription_cancelled');
        $expectedCancellationTime = Carbon::parse($fixtureData['resource']['status_update_time']);

        // Using Log::spy() to be resilient to other logs, similar to the activated tests
        Log::spy();

        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.CANCELLED',
            ['id' => $paypalSubIdFromFixture], // Pass ID to ensure fixture's resource.id is used
            'billing_subscription_cancelled' // Use the new fixture
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('cancelled', $localSubscription->status);
        $this->assertTrue($expectedCancellationTime->equalTo($localSubscription->cancelled_at));
        $this->assertNotNull($localSubscription->paypal_payload['event_cancelled']);

        // Verify specific logs if necessary, e.g.
        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, "Subscription {$paypalSubIdFromFixture} CANCELLED for User ID: {$user->id}"))
            ->once();
    }

    public function test_billing_subscription_cancelled_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-CANCELDOESNOTEXIST';

        Log::spy();

        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.CANCELLED',
            ['id' => $nonExistentPayPalSubId] // Use an ID that won't match
        );
        // Minimal resource data is fine here as we're testing the "no match" path.

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        Log::shouldHaveReceived('warning')
            ->with("Webhook 'BILLING.SUBSCRIPTION.CANCELLED': No local subscription found for PayPal ID: {$nonExistentPayPalSubId}.")
            ->once();
    }

    // Test for BILLING.SUBSCRIPTION.EXPIRED
    public function test_billing_subscription_expired_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubIdFromFixture = 'I-PAYPALSUBID789'; // Matches ID in expired fixture

        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubIdFromFixture,
            'status' => 'active', // Assuming it was active before expiring
        ]);

        $fixtureData = $this->getFixture('billing_subscription_expired');
        // The controller uses status_update_time for ends_at for expired event
        $expectedEndTime = Carbon::parse($fixtureData['resource']['status_update_time']);


        Log::spy();

        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.EXPIRED',
            ['id' => $paypalSubIdFromFixture],
            'billing_subscription_expired'
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('expired', $localSubscription->status);
        $this->assertTrue($expectedEndTime->equalTo($localSubscription->ends_at));
        $this->assertNotNull($localSubscription->paypal_payload['event_expired']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, "Subscription {$paypalSubIdFromFixture} EXPIRED for User ID: {$user->id}"))
            ->once();
    }

    public function test_billing_subscription_expired_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-EXPIREDOESNOTEXIST';
        Log::spy();
        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.EXPIRED',
            ['id' => $nonExistentPayPalSubId]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        Log::shouldHaveReceived('warning')
            ->with("Webhook 'BILLING.SUBSCRIPTION.EXPIRED': No local subscription found for PayPal ID: {$nonExistentPayPalSubId}.")
            ->once();
    }

    // Test for BILLING.SUBSCRIPTION.SUSPENDED
    public function test_billing_subscription_suspended_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubIdFromFixture = 'I-PAYPALSUBIDABC'; // Matches ID in suspended fixture

        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubIdFromFixture,
            'status' => 'active', // Assuming it was active before suspension
        ]);

        Log::spy();

        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.SUSPENDED',
            ['id' => $paypalSubIdFromFixture],
            'billing_subscription_suspended'
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('suspended', $localSubscription->status);
        $this->assertNotNull($localSubscription->paypal_payload['event_suspended']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, "Subscription {$paypalSubIdFromFixture} SUSPENDED for User ID: {$user->id}"))
            ->once();
    }

    public function test_billing_subscription_suspended_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-SUSPENDEDDOESNOTEXIST';
        Log::spy();
        $response = $this->simulateWebhook(
            'BILLING.SUBSCRIPTION.SUSPENDED',
            ['id' => $nonExistentPayPalSubId]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        Log::shouldHaveReceived('warning')
            ->with("Webhook 'BILLING.SUBSCRIPTION.SUSPENDED': No local subscription found for PayPal ID: {$nonExistentPayPalSubId}.")
            ->once();
    }

    // Test for PAYMENT.SALE.COMPLETED
    public function test_payment_sale_completed_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubId = 'I-PAYPALSUBIDFORPAYMENT'; // Matches ID in payment fixtures

        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'active', // Could be active or past_due if payment retrying
        ]);

        Log::spy();

        $response = $this->simulateWebhook(
            'PAYMENT.SALE.COMPLETED',
            ['billing_agreement_id' => $paypalSubId], // Key part for matching
            'payment_sale_completed'
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('active', $localSubscription->status); // Should ensure it's active
        $this->assertNotEmpty($localSubscription->paypal_payload['event_payment_sale_completed_']);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, "Payment received for Subscription {$paypalSubId}"))
            ->once();
    }

    public function test_payment_sale_completed_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-PAYMENTNOEXIST';
        Log::spy();
        $response = $this->simulateWebhook(
            'PAYMENT.SALE.COMPLETED',
            ['billing_agreement_id' => $nonExistentPayPalSubId]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        Log::shouldHaveReceived('warning')
            ->with("Webhook 'PAYMENT.SALE.COMPLETED': No local subscription found for PayPal Subscription ID: {$nonExistentPayPalSubId}.")
            ->once();
    }

    // Test for PAYMENT.SALE.DENIED
    public function test_payment_sale_denied_webhook_updates_local_subscription(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();
        $paypalSubId = 'I-PAYPALSUBIDFORPAYMENT'; // Matches ID in payment fixtures

        $localSubscription = UserSubscription::factory()->for($user)->for($plan)->create([
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'active',
        ]);

        Log::spy();

        $response = $this->simulateWebhook(
            'PAYMENT.SALE.DENIED',
            ['billing_agreement_id' => $paypalSubId],
            'payment_sale_denied'
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $localSubscription->refresh();
        $this->assertEquals('past_due', $localSubscription->status); // As per controller logic
        $this->assertNotEmpty($localSubscription->paypal_payload['event_PAYMENT.SALE.DENIED_' . ($this->getFixture('payment_sale_denied')['resource']['id'] ?? '')]);


        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, "Webhook 'PAYMENT.SALE.DENIED' received for Subscription {$paypalSubId}"))
            ->once();
    }

    public function test_payment_sale_denied_webhook_logs_warning_if_no_local_match(): void
    {
        $nonExistentPayPalSubId = 'I-DENIEDNOEXIST';
        Log::spy();
        $response = $this->simulateWebhook(
            'PAYMENT.SALE.DENIED',
            ['billing_agreement_id' => $nonExistentPayPalSubId]
        );
        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        Log::shouldHaveReceived('warning')
            ->with("Webhook 'PAYMENT.SALE.DENIED': No local subscription found for PayPal Subscription ID: {$nonExistentPayPalSubId}.")
            ->once();
    }
}
