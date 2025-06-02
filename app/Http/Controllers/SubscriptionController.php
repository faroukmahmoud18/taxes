<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\UserSubscription; // Added
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // For better logging
// PayPal SDK classes
use PayPal\Core\PayPalHttpClient;
use PayPal\Core\SandboxEnvironment;
use PayPal\Core\ProductionEnvironment;
use PayPal\v1\Subscriptions\SubscriptionsCreateRequest;
use PayPalHttp\HttpException; // Corrected namespace for PayPal SDK's HttpException

class SubscriptionController extends Controller
{
    private $payPalClient;

    public function __construct()
    {
        // Ensure PayPal client is initialized only if not already done by a previous call in the same request lifecycle (though unlikely for controller)
        if (!$this->payPalClient) {
            $this->initializePayPalClient();
        }
    }

    private function initializePayPalClient()
    {
        try {
            $config = config('services.paypal');
            // Added more specific checks for keys to prevent errors if config is partially set
            if (!isset($config['mode']) ||
                !isset($config[$config['mode']]) ||
                !isset($config[$config['mode']]['client_id']) ||
                !isset($config[$config['mode']]['client_secret'])) {
                Log::error('PayPal configuration missing or incomplete in config/services.php or .env');
                throw new \Exception('PayPal configuration is missing or incomplete.');
            }
            $environmentMode = $config['mode'];
            $credentials = $config[$environmentMode];

            if (empty($credentials['client_id']) || empty($credentials['client_secret'])) {
                Log::error("PayPal client_id or client_secret not configured for {$environmentMode} mode.");
                throw new \Exception("PayPal client_id or client_secret not configured for {$environmentMode} mode.");
            }

            if ($environmentMode === 'sandbox') {
                $environment = new SandboxEnvironment($credentials['client_id'], $credentials['client_secret']);
            } else {
                $environment = new ProductionEnvironment($credentials['client_id'], $credentials['client_secret']);
            }
            $this->payPalClient = new PayPalHttpClient($environment);
            Log::info("PayPal client initialized for mode: {$environmentMode}");
        } catch (\Exception $e) {
            Log::error('PayPal Client Initialization Error: ' . $e->getMessage());
            $this->payPalClient = null;
        }
    }

    public function index()
    {
        $plans = SubscriptionPlan::whereNotNull('paypal_plan_id')->whereNull('deleted_at')->get();
        return view('subscriptions.index', compact('plans'));
    }

    public function subscribe(Request $request, SubscriptionPlan $plan)
    {
        if (!$this->payPalClient) {
             Log::error("PayPal client not initialized in subscribe(). Cannot proceed for plan ID: {$plan->id} User ID: " . Auth::id());
             return redirect()->route('subscriptions.index')->with('error', 'Payment system error. Please try again later.');
        }

        if (empty($plan->paypal_plan_id)) {
            Log::warning("Attempt to subscribe to plan ID {$plan->id} which has no PayPal Plan ID. User ID: " . Auth::id());
            return redirect()->route('subscriptions.index')->with('error', 'This plan is not configured for PayPal subscriptions.');
        }

        $user = Auth::user();

        if ($user->hasActiveSubscription()) {
             Log::info("User ID: {$user->id} attempted to subscribe to plan ID {$plan->id} but already has an active subscription.");
             return redirect()->route('dashboard')->with('info', 'You already have an active subscription.');
        }

        $payPalRequest = new SubscriptionsCreateRequest();
        $payPalRequest->body = [
            'plan_id' => $plan->paypal_plan_id,
            'subscriber' => [
                'name' => [
                    'given_name' => $user->name,
                ],
                'email_address' => $user->email,
            ],
            'application_context' => [
                'brand_name' => config('app.name', 'Laravel SaaS'),
                'locale' => str_replace('_', '-', app()->getLocale()) . '-' . strtoupper(str_replace('_', '-', app()->getLocale())), // e.g., en-US, de-DE
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => route('subscriptions.success'),
                'cancel_url' => route('subscriptions.cancel'),
            ],
        ];

        try {
            Log::info("Creating PayPal subscription for User ID: {$user->id}, PayPal Plan ID: {$plan->paypal_plan_id}");
            $response = $this->payPalClient->execute($payPalRequest);

            if ($response->statusCode == 201 && isset($response->result->id) && isset($response->result->links)) {
                $payPalSubscriptionId = $response->result->id;

                UserSubscription::updateOrCreate(
                    ['user_id' => $user->id, 'paypal_subscription_id' => $payPalSubscriptionId], // Check by paypal_subscription_id too
                    [
                        'subscription_plan_id' => $plan->id,
                        'status' => 'pending_approval',
                        'starts_at' => now(),
                        'paypal_payload' => $response->result,
                    ]
                );

                Log::info("Local subscription record created/updated for User ID: {$user->id} with PayPal Subscription ID: {$payPalSubscriptionId}. Status: pending_approval.");

                foreach ($response->result->links as $link) {
                    if ($link->rel === 'approve') {
                        Log::info("Redirecting User ID: {$user->id} to PayPal approval URL: {$link->href}");
                        return redirect()->away($link->href);
                    }
                }
                Log::error("No approval link found in PayPal response for User ID: {$user->id}, PayPal Subscription ID: {$payPalSubscriptionId}");
                return redirect()->route('subscriptions.index')->with('error', 'Could not retrieve PayPal approval link. Please try again.');
            } else {
                Log::error("PayPal subscription creation failed for User ID: {$user->id}. Status: {$response->statusCode}, Response: " . json_encode($response->result));
                return redirect()->route('subscriptions.index')->with('error', 'Failed to create PayPal subscription. Please try again.');
            }
        } catch (HttpException $e) {
            Log::error("PayPal API HttpException for User ID: {$user->id}: " . $e->getMessage() . " Status Code: " . $e->statusCode);
            $errorBody = json_decode($e->getMessage());
            if (json_last_error() === JSON_ERROR_NONE && isset($errorBody->details)) {
                foreach($errorBody->details as $detail) {
                    Log::error("Detail: {$detail->issue} - {$detail->description}");
                }
            } else {
                 Log::error("Raw error body: " . $e->getMessage());
            }
            return redirect()->route('subscriptions.index')->with('error', 'An error occurred with PayPal. Please try again later.');
        } catch (\Exception $e) {
            Log::error("Generic Exception during PayPal subscription for User ID: {$user->id}: " . $e->getMessage());
            return redirect()->route('subscriptions.index')->with('error', 'An unexpected error occurred. Please try again later.');
        }
    }

    public function success(Request $request)
    {
        $user = Auth::user();
        $token = $request->query('token');
        $payPalSubscriptionId = $request->query('subscription_id');

        Log::info("User ID: {$user->id} returned from PayPal successfully. Token: {$token}, PayPal Subscription ID: {$payPalSubscriptionId}");

        if (empty($payPalSubscriptionId)) {
            Log::warning("User ID: {$user->id} returned to success URL without a subscription_id.");
            return redirect()->route('subscriptions.index')->with('error', 'Subscription confirmation from PayPal is missing an ID. Please check your dashboard or contact support.');
        }

        $localSub = UserSubscription::where('user_id', $user->id)
                                  ->where('paypal_subscription_id', $payPalSubscriptionId)
                                  ->first();

        if ($localSub) {
            if($localSub->status === 'pending_approval') {
                 $localSub->status = 'pending_webhook_confirmation';
                 $localSub->save();
                 Log::info("User ID: {$user->id}, Local Subscription ID: {$localSub->id} status updated to {$localSub->status} after PayPal approval redirect.");
            }
            return redirect()->route('dashboard')->with('success', 'Thank you! Your subscription with PayPal is approved and will be activated shortly.');
        } else {
            Log::warning("User ID: {$user->id} returned to success URL, but no matching local subscription found for PayPal ID: {$payPalSubscriptionId}.");
            return redirect()->route('dashboard')->with('warning', 'Your PayPal transaction was successful, but we encountered an issue linking it to your account. Please contact support.');
        }
    }

    public function cancel(Request $request)
    {
        $user = Auth::user();
        Log::info("User ID: {$user->id} cancelled PayPal payment process.");
        UserSubscription::where('user_id', $user->id)
                        ->where('status', 'pending_approval')
                        ->update(['status' => 'cancelled_by_user_at_paypal', 'ends_at' => now()]);

        return redirect()->route('subscriptions.index')->with('info', 'You have cancelled the subscription process.');
    }
}
