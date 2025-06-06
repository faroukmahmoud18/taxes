<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http; // Used for mocking PayPalHttpClient, though actual SDK uses its own client.
use Tests\TestCase;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use PayPal\Core\PayPalHttpClient;
use PayPal\Http\HttpResponse;
use Mockery; // For mocking PayPalHttpClient

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $payPalClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock PayPalHttpClient
        // We use Mockery as PayPalHttpClient is not typically resolved via Laravel's service container for Http::fake()
        $this->payPalClientMock = Mockery::mock(PayPalHttpClient::class);
        $this->app->instance(PayPalHttpClient::class, $this->payPalClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(bool $isAdmin = false): User
    {
        return User::factory()->create(['is_admin' => $isAdmin]);
    }

    private function createSubscriptionPlan(array $attributes = []): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create(array_merge([
            'name' => ['en' => 'Basic Plan', 'de' => 'Basisplan', 'ar' => 'الخطة الأساسية'],
            'features' => ['en' => 'Feature 1', 'de' => 'Merkmal 1', 'ar' => 'ميزة 1'],
            'price' => 9.99,
            'paypal_plan_id' => 'P-TESTPLAN123',
        ], $attributes));
    }

    public function test_user_can_initiate_subscription_and_is_redirected()
    {
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();

        $mockedPayPalSubscriptionId = 'I-MOCKPAYPALSUBSID';
        $mockedApprovalLink = 'https://www.sandbox.paypal.com/checkoutnow?token=' . $mockedPayPalSubscriptionId;

        $mockResponse = new HttpResponse(
            201, // Created
            json_encode([
                'id' => $mockedPayPalSubscriptionId,
                'status' => 'APPROVAL_PENDING',
                'links' => [
                    ['href' => $mockedApprovalLink, 'rel' => 'approve', 'method' => 'GET'],
                    ['href' => 'self_link', 'rel' => 'self', 'method' => 'GET'],
                ]
            ]),
            [] // No specific headers needed for mock body
        );

        $this->payPalClientMock
            ->shouldReceive('execute')
            // ->with(Mockery::type(\PayPalHttp\HttpRequest::class)) // More specific check if needed
            ->once()
            ->andReturn($mockResponse);

        $response = $this->actingAs($user)
                         ->post(route('subscriptions.subscribe', $plan->id));

        $response->assertStatus(302);
        $response->assertRedirect($mockedApprovalLink);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'paypal_subscription_id' => $mockedPayPalSubscriptionId,
            'status' => 'pending_approval',
        ]);

        $userSubscription = UserSubscription::where('paypal_subscription_id', $mockedPayPalSubscriptionId)->first();
        $this->assertNotNull($userSubscription);
        $this->assertNotEmpty($userSubscription->paypal_payload);
        $this->assertArrayHasKey('request', $userSubscription->paypal_payload);
        $this->assertArrayHasKey('response', $userSubscription->paypal_payload);
    }

    public function test_subscription_success_callback_updates_status()
    {
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $userSubscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'paypal_subscription_id' => 'I-SUBSCRIBERIDSUCCESS',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($user)
                         ->get(route('subscriptions.success', ['subscription_id' => $userSubscription->paypal_subscription_id]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Thank you! Your subscription with PayPal is approved and will be activated shortly.');

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $userSubscription->id,
            'status' => 'pending_webhook_confirmation',
        ]);
    }

    public function test_subscription_cancel_callback_updates_status()
    {
        $user = $this->createUser();
        $plan = $this->createSubscriptionPlan();
        $userSubscription = UserSubscription::factory()->create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'paypal_subscription_id' => 'I-SUBSCRIBERIDCANCEL',
            'status' => 'pending_approval',
        ]);

        $response = $this->actingAs($user)
                         ->get(route('subscriptions.cancel'));

        $response->assertRedirect(route('subscriptions.index'));
        $response->assertSessionHas('info', 'You have cancelled the subscription process.');

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $userSubscription->id,
            'status' => 'cancelled_by_user_at_paypal',
        ]);

        $updatedSubscription = $userSubscription->fresh();
        $this->assertNotNull($updatedSubscription->ends_at); // ends_at should be set
    }
}
