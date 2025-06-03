<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PayPal\Core\PayPalHttpClient;
use PayPal\Core\SandboxEnvironment;
use PayPal\Core\ProductionEnvironment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PayPalHttpClient::class, function ($app) {
            try {
                $config = Config::get('services.paypal');
                if (!isset($config['mode'], $config[$config['mode']]['client_id'], $config[$config['mode']]['client_secret']) ||
                    empty($config[$config['mode']]['client_id']) || empty($config[$config['mode']]['client_secret'])) {
                    Log::error('PayPal configuration missing, incomplete, or client_id/secret empty for DI in AppServiceProvider.');
                    return null; // Controller should handle this null client
                }
                $environmentMode = $config['mode'];
                $credentials = $config[$environmentMode];

                if ($environmentMode === 'sandbox') {
                    $environment = new SandboxEnvironment($credentials['client_id'], $credentials['client_secret']);
                } else {
                    $environment = new ProductionEnvironment($credentials['client_id'], $credentials['client_secret']);
                }
                Log::info("PayPalHttpClient instantiated for {$environmentMode} mode via AppServiceProvider.");
                return new PayPalHttpClient($environment);
            } catch (\Exception $e) {
                Log::error('Failed to create PayPalHttpClient for DI in AppServiceProvider: ' . $e->getMessage());
                return null; // Controller should handle this null client
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
