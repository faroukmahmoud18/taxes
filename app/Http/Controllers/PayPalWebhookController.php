<?php
// // WebhookControllerUpdatedMarker_V2
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UserSubscription;
use App\Models\SubscriptionPlan; 
use App\Models\User; 
use Carbon\Carbon; 
use PayPalHttp\HttpException; 

class PayPalWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        Log::info('PayPal Webhook Received:', ['payload' => $payload]);

        $isVerified = $this->verifyPayPalWebhookSignatureConceptual($request);
        if (!$isVerified) {
            Log::critical('PayPal Webhook: SIGNATURE VERIFICATION FAILED OR BYPASSED. Event processing aborted.');
            return response()->json(['status' => 'error', 'message' => 'Signature verification failed or not properly configured.'], 400);
        }
        Log::info('PayPal Webhook: Signature (conceptually) verified.');

        $eventType = strtoupper($payload['event_type'] ?? '');
        $resource = $payload['resource'] ?? null;

        if (!$eventType || !$resource || !is_array($resource)) {
            Log::warning('PayPal Webhook: Missing event_type or resource, or resource is not an array.', $payload);
            return response()->json(['status's' => 'error', 'message' => 'Invalid payload (missing event_type or resource)'], 400);
        }

        Log::info("Processing PayPal Webhook event: {$eventType}");

        try {
            switch ($eventType) {
                case 'BILLING.SUBSCRIPTION.ACTIVATED':
                    $this->handleSubscriptionActivated($resource);
                    break;
                case 'BILLING.SUBSCRIPTION.CANCELLED':
                    $this->handleSubscriptionCancelled($resource);
                    break;
                case 'BILLING.SUBSCRIPTION.EXPIRED':
                    $this->handleSubscriptionExpired($resource);
                    break;
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                    $this->handleSubscriptionSuspended($resource);
                    break;
                case 'PAYMENT.SALE.COMPLETED':
                    $this->handlePaymentSaleCompleted($resource);
                    break;
                case 'PAYMENT.SALE.DENIED':
                case 'PAYMENT.SALE.REFUNDED':
                case 'PAYMENT.SALE.REVERSED':
                    $this->handlePaymentSaleOther($eventType, $resource);
                    break;
                default:
                    Log::info("PayPal Webhook: Received unhandled event_type '{$eventType}'.");
            }
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error("PayPal Webhook: Error processing event {$eventType}: " . $e->getMessage(), [
                'exception_trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal server error while processing webhook'], 500);
        }
    }

    private function verifyPayPalWebhookSignatureConceptual(Request $request): bool
    {
        $webhookId = config('services.paypal.webhook_id');
        if (empty($webhookId)) {
            Log::error('PayPal Webhook: Webhook ID (services.paypal.webhook_id) is not configured. Signature verification skipped (FAIL).');
            return false; 
        }
        Log::warning("PayPal Webhook: Conceptual signature verification. In production, implement call to /v1/notifications/verify-webhook-signature API. Webhook ID: {$webhookId}");
        return true; 
    }

    protected function handleSubscriptionActivated(array $resource)
    {
        $paypalSubscriptionId = $resource['id'] ?? null;
        if (!$paypalSubscriptionId) { Log::warning('Webhook BILLING.SUBSCRIPTION.ACTIVATED: Missing resource.id.', ['resource' => $resource]); return; }

        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $subscription->status = 'active';
            $subscription->starts_at = isset($resource['start_time']) ? Carbon::parse($resource['start_time']) : now();
            if (isset($resource['billing_info']['next_billing_time'])) {
                $subscription->ends_at = Carbon::parse($resource['billing_info']['next_billing_time']);
            }
            $newPayload = array_merge($subscription->paypal_payload ?? [], ['event_activated' => $resource]);
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
            Log::info("Subscription {$paypalSubscriptionId} ACTIVATED for User ID: {$subscription->user_id}. Ends at: " . ($subscription->ends_at ? $subscription->ends_at->toDateTimeString() : 'N/A'));
        } else {
            Log::warning("Webhook 'BILLING.SUBSCRIPTION.ACTIVATED': No local subscription found for PayPal ID: {$paypalSubscriptionId}.");
        }
    }

    protected function handleSubscriptionCancelled(array $resource)
    {
        $paypalSubscriptionId = $resource['id'] ?? null;
        if (!$paypalSubscriptionId) { Log::warning('Webhook BILLING.SUBSCRIPTION.CANCELLED: Missing resource.id.', ['resource' => $resource]); return; }

        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $subscription->status = 'cancelled';
            $subscription->cancelled_at = isset($resource['status_update_time']) ? Carbon::parse($resource['status_update_time']) : now();
            $newPayload = array_merge($subscription->paypal_payload ?? [], ['event_cancelled' => $resource]);
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
            Log::info("Subscription {$paypalSubscriptionId} CANCELLED for User ID: {$subscription->user_id}. Effective: " . $subscription->cancelled_at->toDateTimeString());
        } else {
            Log::warning("Webhook 'BILLING.SUBSCRIPTION.CANCELLED': No local subscription found for PayPal ID: {$paypalSubscriptionId}.");
        }
    }
    
    protected function handleSubscriptionExpired(array $resource)
    {
        $paypalSubscriptionId = $resource['id'] ?? null;
        if (!$paypalSubscriptionId) { Log::warning('Webhook BILLING.SUBSCRIPTION.EXPIRED: Missing resource.id.', ['resource' => $resource]); return; }
        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $subscription->status = 'expired';
            $subscription->ends_at = isset($resource['status_update_time']) ? Carbon::parse($resource['status_update_time']) : now();
            $newPayload = array_merge($subscription->paypal_payload ?? [], ['event_expired' => $resource]);
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
            Log::info("Subscription {$paypalSubscriptionId} EXPIRED for User ID: {$subscription->user_id}.");
        } else {
            Log::warning("Webhook 'BILLING.SUBSCRIPTION.EXPIRED': No local subscription found for PayPal ID: {$paypalSubscriptionId}.");
        }
    }

    protected function handleSubscriptionSuspended(array $resource)
    {
        $paypalSubscriptionId = $resource['id'] ?? null;
        if (!$paypalSubscriptionId) { Log::warning('Webhook BILLING.SUBSCRIPTION.SUSPENDED: Missing resource.id.', ['resource' => $resource]); return; }
        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $subscription->status = 'suspended';
            $newPayload = array_merge($subscription->paypal_payload ?? [], ['event_suspended' => $resource]);
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
            Log::info("Subscription {$paypalSubscriptionId} SUSPENDED for User ID: {$subscription->user_id}.");
        } else {
            Log::warning("Webhook 'BILLING.SUBSCRIPTION.SUSPENDED': No local subscription found for PayPal ID: {$paypalSubscriptionId}.");
        }
    }

    protected function handlePaymentSaleCompleted(array $resource)
    {
        $paypalSubscriptionId = $resource['billing_agreement_id'] ?? null; 
        if (!$paypalSubscriptionId) { Log::warning("Webhook 'PAYMENT.SALE.COMPLETED': Missing billing_agreement_id.", ['resource' => $resource]); return; }

        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $subscription->status = 'active'; 
            Log::info("Webhook 'PAYMENT.SALE.COMPLETED': Payment received for Subscription {$paypalSubscriptionId}, User ID: {$subscription->user_id}. 'ends_at' should be updated based on new billing cycle from PayPal.");
            $newPayload = $subscription->paypal_payload ?? [];
            $newPayload['event_payment_sale_completed_'] = $newPayload['event_payment_sale_completed_'] ?? []; 
            $newPayload['event_payment_sale_completed_'][] = $resource; 
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
        } else {
            Log::warning("Webhook 'PAYMENT.SALE.COMPLETED': No local subscription found for PayPal Subscription ID: {$paypalSubscriptionId}.");
        }
    }

    protected function handlePaymentSaleOther($eventType, array $resource)
    {
        $paypalSubscriptionId = $resource['billing_agreement_id'] ?? null;
        if (!$paypalSubscriptionId) { Log::warning("Webhook {$eventType}: Missing billing_agreement_id.", ['resource' => $resource]); return; }
        $subscription = UserSubscription::where('paypal_subscription_id', $paypalSubscriptionId)->first();
        if ($subscription) {
            $newStatus = 'payment_issue'; 
            if (strtoupper($eventType) === 'PAYMENT.SALE.DENIED') {
                $newStatus = 'past_due'; 
            }
            $subscription->status = $newStatus;
            $newPayload = $subscription->paypal_payload ?? [];
            $newPayload["event_{$eventType}_" . ($resource['id'] ?? time())] = $resource;
            $subscription->paypal_payload = $newPayload;
            $subscription->save();
            Log::info("Webhook '{$eventType}' received for Subscription {$paypalSubscriptionId}, User ID: {$subscription->user_id}. Status set to '{$newStatus}'.");
        } else {
            Log::warning("Webhook '{$eventType}': No local subscription found for PayPal Subscription ID: {$paypalSubscriptionId}.");
        }
    }
}
