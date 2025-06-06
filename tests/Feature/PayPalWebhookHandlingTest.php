<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use PayPal\Core\PayPalHttpClient;
use PayPal\Http\HttpResponse;
use Mockery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log; // For debugging test setup if needed

class PayPalWebhookHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected $payPalClientMock;
    protected $webhookId;
    protected $sampleHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payPalClientMock = Mockery::mock(PayPalHttpClient::class);
        $this->app->instance(PayPalHttpClient::class, $this->payPalClientMock);

        $this->webhookId = 'test_webhook_id_from_config';
        Config::set('services.paypal.webhook_id', $this->webhookId);

        $this->sampleHeaders = [
            'PAYPAL-TRANSMISSION-ID' => 'test_transmission_id',
            'PAYPAL-TRANSMISSION-TIME' => now()->toIso8601String(),
            'PAYPAL-CERT-URL' => 'https://api.sandbox.paypal.com/v1/notifications/certs/test-cert-url',
            'PAYPAL-AUTH-ALGO' => 'SHA256withRSA',
            'PAYPAL-TRANSMISSION-SIG' => 'test_signature',
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createSubscriptionPlan(): SubscriptionPlan
    {
         return SubscriptionPlan::factory()->create([
            'name' => ['en' => 'Pro Plan', 'de' => 'Pro Plan', 'ar' => 'الخطة الاحترافية'],
            'features' => ['en' => 'Feature Pro', 'de' => 'Merkmal Pro', 'ar' => 'ميزة احترافية'],
            'price' => 19.99,
            'paypal_plan_id' => 'P-PROPLANPAYPAL',
        ]);
    }

    private function createUserSubscription(User $user, SubscriptionPlan $plan, array $attributes = []): UserSubscription
    {
        return UserSubscription::factory()->create(array_merge([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
        ], $attributes));
    }

    protected function mockPayPalVerification(bool $success = true, $verificationStatus = 'SUCCESS')
    {
        $responseBody = ['verification_status' => $verificationStatus];
        $statusCode = 200;

        if (!$success && $verificationStatus === 'FAILURE') { // Specific failure case
             $responseBody = ['verification_status' => 'FAILURE'];
        } elseif (!$success) { // General API call failure
            $statusCode = 500; // Or any non-200
            $responseBody = ['error' => 'API Error'];
        }

        $mockResponse = new HttpResponse($statusCode, json_encode($responseBody), []);

        $this->payPalClientMock
            ->shouldReceive('execute')
            ->withArgs(function ($request) {
                // Ensure this mock is only for the verify-webhook-signature call
                return str_contains($request->path, '/v1/notifications/verify-webhook-signature');
            })
            ->andReturn($mockResponse);
    }

    protected function mockPayPalVerificationException(\Exception $exception)
    {
        $this->payPalClientMock
            ->shouldReceive('execute')
            ->withArgs(function ($request) {
                return str_contains($request->path, '/v1/notifications/verify-webhook-signature');
            })
            ->andThrow($exception);
    }

    public function test_webhook_handles_billing_subscription_activated()
    {
        $this->mockPayPalVerification(true);
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $paypalSubId = 'test_sub_id_activated';
        $subscription = $this->createUserSubscription($user, $plan, [
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'pending_webhook_confirmation',
        ]);

        $startTime = Carbon::now()->subDay()->toIso8601String();
        $nextBillingTime = Carbon::now()->addMonth()->toIso8601String();

        $payload = [
            'id' => 'EVT-ACTIVATED-123',
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource_type' => 'subscription',
            'resource_version' => '1.0',
            'summary' => 'Subscription activated',
            'resource' => [
                'id' => $paypalSubId,
                'plan_id' => $plan->paypal_plan_id,
                'start_time' => $startTime,
                'status' => 'ACTIVE',
                'billing_info' => [
                    'next_billing_time' => $nextBillingTime,
                ],
            ],
            'create_time' => Carbon::now()->toIso8601String(),
        ];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'starts_at' => Carbon::parse($startTime)->format('Y-m-d H:i:s'), // DB format
            'ends_at' => Carbon::parse($nextBillingTime)->format('Y-m-d H:i:s'),
        ]);
        $updatedSub = $subscription->fresh();
        $this->assertArrayHasKey('event_activated', $updatedSub->paypal_payload);
    }

    public function test_webhook_handles_billing_subscription_cancelled()
    {
        $this->mockPayPalVerification(true);
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $paypalSubId = 'test_sub_id_cancelled';
        $subscription = $this->createUserSubscription($user, $plan, [
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'active',
        ]);
        $statusUpdateTime = Carbon::now()->subHour()->toIso8601String();

        $payload = [
            'id' => 'EVT-CANCELLED-123',
            'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
            'resource' => ['id' => $paypalSubId, 'status_update_time' => $statusUpdateTime],
        ];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
            'cancelled_at' => Carbon::parse($statusUpdateTime)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_webhook_handles_billing_subscription_expired()
    {
        $this->mockPayPalVerification(true);
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $paypalSubId = 'test_sub_id_expired';
        $subscription = $this->createUserSubscription($user, $plan, [
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'active', // Or 'suspended'
        ]);
        $statusUpdateTime = Carbon::now()->toIso8601String();

        $payload = [
            'id' => 'EVT-EXPIRED-123',
            'event_type' => 'BILLING.SUBSCRIPTION.EXPIRED',
            'resource' => ['id' => $paypalSubId, 'status_update_time' => $statusUpdateTime],
        ];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);
        $response->assertStatus(200);
        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => 'expired',
            'ends_at' => Carbon::parse($statusUpdateTime)->format('Y-m-d H:i:s'),
        ]);
    }

    public function test_webhook_handles_payment_sale_completed()
    {
        $this->mockPayPalVerification(true);
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $paypalSubId = 'test_sub_id_payment';
        $subscription = $this->createUserSubscription($user, $plan, [
            'paypal_subscription_id' => $paypalSubId,
            'status' => 'active',
        ]);

        $payload = [
            'id' => 'EVT-PAYMENT-123',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => ['billing_agreement_id' => $paypalSubId, 'id' => 'PAYMENTID123'],
        ];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);
        $response->assertStatus(200);
        // Status should remain active or be updated based on specific logic not detailed here for PAYMENT.SALE.COMPLETED
        // The main assertion is that the event is logged in paypal_payload
        $updatedSub = $subscription->fresh();
        $this->assertNotNull($updatedSub->paypal_payload);
        $this->assertIsArray($updatedSub->paypal_payload['event_payment_sale_completed_']);
        $this->assertCount(1, $updatedSub->paypal_payload['event_payment_sale_completed_']);
        $this->assertEquals('PAYMENTID123', $updatedSub->paypal_payload['event_payment_sale_completed_'][0]['id']);
    }

    public function test_webhook_aborts_if_signature_verification_fails()
    {
        $this->mockPayPalVerification(false, 'FAILURE'); // Explicitly mock verification failure
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $subscription = $this->createUserSubscription($user, $plan, ['status' => 'active', 'paypal_subscription_id' => 'sub_nochange']);

        $originalStatus = $subscription->status;

        $payload = ['event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'sub_nochange']];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);

        $response->assertStatus(400); // Or whatever status code your controller returns for verification failure
        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => $originalStatus, // Status should not have changed
        ]);
    }

    public function test_webhook_aborts_if_paypal_verification_call_fails()
    {
        // Mock an HttpException from PayPal SDK
        $this->mockPayPalVerificationException(
            new \PayPalHttp\HttpException("Mock API Failure", 500, new HttpResponse(500, json_encode(['error' => 'Server Error']), []))
        );

        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $subscription = $this->createUserSubscription($user, $plan, ['status' => 'active', 'paypal_subscription_id' => 'sub_nochange_apifail']);
        $originalStatus = $subscription->status;

        $payload = ['event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'resource' => ['id' => 'sub_nochange_apifail']];

        $response = $this->postJson(route('paypal.webhook'), $payload, $this->sampleHeaders);

        $response->assertStatus(400); // Controller returns 400 if verification fails for any reason including API call
        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscription->id,
            'status' => $originalStatus,
        ]);
    }
}
