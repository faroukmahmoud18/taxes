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
// use PayPal\v1\Subscriptions\SubscriptionsCreateRequest; // SDK does not have this class for this version.
use PayPalHttp\HttpRequest; // Added for generic requests
use PayPalHttp\HttpException;

class SubscriptionController extends Controller
{
    private $payPalClient;

    // Inject PayPalHttpClient via constructor
    public function __construct(PayPalHttpClient $payPalClient = null) // Allow null for graceful failure if DI fails
    {
        $this->payPalClient = $payPalClient;
        if (is_null($payPalClient)) {
            Log::error("PayPalHttpClient not injected or failed to initialize via ServiceProvider for SubscriptionController.");
            // Optionally, you could try a fallback initialization here, but it's better if DI handles it.
            // For now, methods using $this->payPalClient will need to check if it's null.
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

        // Construct the request body for PayPal
        $requestBody = [
            'plan_id' => $plan->paypal_plan_id,
            'start_time' => date('Y-m-d\TH:i:s\Z', time() + 60), // e.g., start in 1 minute
            // 'subscriber' => [ // Optional: Not strictly needed if user logs in on PayPal side
            //     'name' => [
            //         'given_name' => $user->name, // Assuming user has a 'name' attribute
            //     ],
            //     'email_address' => $user->email,
            // ],
            'application_context' => [
                'brand_name' => config('app.name', 'Laravel App'),
                'locale' => 'en-US',
                'shipping_preference' => 'NO_SHIPPING', // For digital goods
                'user_action' => 'SUBSCRIBE_NOW',
                'payment_method' => [
                    'payer_selected' => 'PAYPAL',
                    'payee_preferred' => 'IMMEDIATE_PAYMENT_REQUIRED',
                ],
                'return_url' => route('subscriptions.success'),
                'cancel_url' => route('subscriptions.cancel'),
            ],
        ];

        // Create a generic request for PayPal API
        // Note: The PayPal SDK v1.1.0 might not have a specific 'SubscriptionsCreateRequest'
        // We might need to use a generic HttpRequest or craft it carefully.
        // The paypal/paypal-server-sdk for v1 typically uses classes like `PayPal\v1\Billing\SubscriptionsCreateRequest`
        // but the log indicates it's missing. We will use a generic approach.

        $payPalRequest = new HttpRequest('/v1/billing/subscriptions', 'POST');
        $payPalRequest->headers['Content-Type'] = 'application/json';
        // $payPalRequest->headers['PayPal-Request-Id'] = 'sub-' . uniqid(); // Optional: for idempotency
        $payPalRequest->body = $requestBody;

        try {
            Log::info("Attempting to create PayPal subscription for User ID: {$user->id}, Plan ID: {$plan->id}.");
            $response = $this->payPalClient->execute($payPalRequest);
            $result = $response->result; // This is typically an object or array from JSON response

            if ($response->statusCode == 201 && isset($result->id)) {
                $payPalSubscriptionId = $result->id;
                $approvalLink = null;
                if (isset($result->links)) {
                    foreach ($result->links as $link) {
                        if ($link->rel == 'approve') {
                            $approvalLink = $link->href;
                            break;
                        }
                    }
                }

                if (!$approvalLink) {
                    Log::error("PayPal subscription created (ID: {$payPalSubscriptionId}) for User ID: {$user->id}, but no approval link found in response. Response: " . json_encode($result));
                    return redirect()->route('subscriptions.index')->with('error', 'Could not retrieve PayPal approval link. Please try again.');
                }

                // Create/Update local UserSubscription record
                UserSubscription::updateOrCreate(
                    ['user_id' => $user->id, 'subscription_plan_id' => $plan->id], // Find by user and plan
                    [
                        'paypal_subscription_id' => $payPalSubscriptionId,
                        'status' => 'pending_approval',
                        'starts_at' => now(), // Or parse from PayPal response if available and relevant
                        'paypal_payload' => json_encode(['request' => $requestBody, 'response' => $result]), // Store request and response
                    ]
                );
                Log::info("Local subscription record created/updated for User ID: {$user->id}. PayPal Subscription ID: {$payPalSubscriptionId}. Status: pending_approval. Redirecting to approval link.");
                return redirect()->away($approvalLink);

            } else {
                Log::error("PayPal subscription creation failed or returned unexpected status for User ID: {$user->id}. Status: {$response->statusCode}. Response: " . json_encode($result));
                return redirect()->route('subscriptions.index')->with('error', 'Failed to initiate PayPal subscription. Please try again.');
            }

        } catch (HttpException $e) {
            Log::error("PayPal API HttpException for User ID: {$user->id}, Plan ID: {$plan->id} during subscription creation. Message: {$e->getMessage()}. Debug ID: {$e->getPayPalDebugId()}. Full Error: " . $e->getDebugMessage());
            // $e->getDebugMessage() often contains the JSON response from PayPal with more details.
            return redirect()->route('subscriptions.index')->with('error', "Error communicating with PayPal: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("Generic exception for User ID: {$user->id}, Plan ID: {$plan->id} during PayPal subscription creation. Message: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}");
            return redirect()->route('subscriptions.index')->with('error', 'An unexpected error occurred while setting up your subscription. Please try again later.');
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
