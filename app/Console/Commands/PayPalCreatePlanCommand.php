<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubscriptionPlan;
use PayPal\Core\PayPalHttpClient;
use PayPal\Core\SandboxEnvironment; // Or ProductionEnvironment
use PayPal\Core\ProductionEnvironment;
use PayPal\v1\Billing\PlansCreateRequest;
// Potentially ProductsCreateRequest if we manage products via API too
// use PayPal\v1\Products\ProductsCreateRequest; // Example for product creation

class PayPalCreatePlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:create-plan {local_plan_id} {--product_id=} {--force_update_on_paypal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates or (optionally) updates a subscription plan on PayPal and links it.';

    private $payPalClient;

    /**
     * Initialize PayPal HTTP client.
     */
    private function initializePayPalClient()
    {
        $config = config('services.paypal');
        if (!$config || !isset($config['mode'], $config['sandbox'], $config['live'])) {
            throw new \Exception('PayPal configuration missing or incomplete in config/services.php');
        }
        $environmentMode = $config['mode'];
        $credentials = $config[$environmentMode] ?? null;

        if (!$credentials || empty($credentials['client_id']) || empty($credentials['client_secret'])) {
            throw new \Exception("PayPal client_id or client_secret not configured for {$environmentMode} mode in .env or config/services.php");
        }

        if ($environmentMode === 'sandbox') {
            $environment = new SandboxEnvironment($credentials['client_id'], $credentials['client_secret']);
        } else {
            $environment = new ProductionEnvironment($credentials['client_id'], $credentials['client_secret']);
        }
        $this->payPalClient = new PayPalHttpClient($environment);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->initializePayPalClient();
        } catch (\Exception $e) {
            $this->error("PayPal Client Initialization Failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $localPlanId = $this->argument('local_plan_id');
        $providedPayPalProductId = $this->option('product_id'); 
        $forceUpdate = $this->option('force_update_on_paypal');


        $localPlan = SubscriptionPlan::find($localPlanId);

        if (!$localPlan) {
            $this->error("Local subscription plan with ID {$localPlanId} not found.");
            return Command::FAILURE;
        }

        if (empty($providedPayPalProductId)) {
            $this->error("A PayPal Product ID must be provided via --product_id=<ID>");
            $this->info("You can create a Product in your PayPal developer dashboard or via the API.");
            $this->info("Example Product: Name: 'My SaaS Service', Type: 'SERVICE', Category: 'SOFTWARE'");
            return Command::FAILURE;
        }

        $this->info("Processing local plan: '{$localPlan->getTranslation('name', 'en')}' (ID: {$localPlanId})");
        $this->info("Using PayPal Product ID: {$providedPayPalProductId}");

        if ($localPlan->paypal_plan_id && !$forceUpdate) {
            $this->info("This local plan already has a PayPal Plan ID: {$localPlan->paypal_plan_id}. Use --force_update_on_paypal to attempt an update (not fully supported by this command for existing PayPal plans).");
            return Command::SUCCESS;
        }

        $request = new PlansCreateRequest();
        $request->body = [
            'product_id' => $providedPayPalProductId,
            'name' => $localPlan->getTranslation('name', 'en'), 
            'description' => $localPlan->getTranslation('features', 'en', false) ?: $localPlan->getTranslation('name', 'en'), 
            'status' => 'ACTIVE', 
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit' => 'MONTH', 
                        'interval_count' => 1,
                    ],
                    'tenure_type' => 'REGULAR', 
                    'sequence' => 1, 
                    'total_cycles' => 0, 
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => number_format($localPlan->price, 2, '.', ''), 
                            'currency_code' => 'EUR', 
                        ],
                    ],
                ],
            ],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE', 
                'payment_failure_threshold' => 3, 
            ],
        ];

        try {
            $this->info("Creating plan on PayPal...");
            $response = $this->payPalClient->execute($request);
            
            if (in_array($response->statusCode, [200, 201])) { // 200 OK or 201 Created
                $payPalPlanId = $response->result->id;
                $this->info("PayPal plan created/updated successfully. PayPal Plan ID: {$payPalPlanId}");

                $localPlan->paypal_plan_id = $payPalPlanId;
                $localPlan->save();
                $this->info("Local plan ID {$localPlanId} updated with PayPal Plan ID.");
            } else {
                $this->error("Failed to create/update PayPal plan. Status Code: {$response->statusCode}");
                if (isset($response->result) && property_exists($response->result, 'details') && is_array($response->result->details)) {
                    foreach ($response->result->details as $detail) {
                        $this->error("Error: {$detail->issue} - {$detail->description}");
                    }
                } else if (isset($response->result) && property_exists($response->result, 'message')) {
                     $this->error("PayPal Error Message: {$response->result->message}");
                }
            }
        } catch (\PayPalHttp\HttpException $e) { // Correctly catch PayPalHttp\HttpException
            $this->error("PayPal API request failed: " . $e->getMessage());
            $this->error("Status Code: " . $e->statusCode);
            $errorBody = json_decode($e->getMessage()); // PayPal SDK often puts JSON in the message
            if (json_last_error() === JSON_ERROR_NONE && isset($errorBody->details)) {
                foreach($errorBody->details as $detail) {
                    $this->error("Detail: {$detail->issue} - {$detail->description}");
                }
            } else {
                $this->error("Raw error body: " . $e->getMessage()); // Fallback if not JSON or no details
            }
        } catch (\Exception $e) {
            $this->error("An unexpected error occurred: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
