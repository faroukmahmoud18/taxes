<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // Added for logging admin user

class TaxConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        $taxConfig = Config::get('tax_rates.germany', []); // Provide default empty array
        
        // Ensure default structure if parts are missing to avoid errors in view
        $taxConfig['year'] = $taxConfig['year'] ?? 'N/A';
        $taxConfig['income_tax'] = $taxConfig['income_tax'] ?? [];
        $taxConfig['income_tax']['single'] = $taxConfig['income_tax']['single'] ?? ['brackets' => []]; // Use 'single_brackets' as per config file
        $taxConfig['income_tax']['single_brackets'] = $taxConfig['income_tax']['single_brackets'] ?? []; // Corrected key
        $taxConfig['income_tax']['solidarity_surcharge'] = $taxConfig['income_tax']['solidarity_surcharge'] ?? [];
        $taxConfig['income_tax']['church_tax'] = $taxConfig['income_tax']['church_tax'] ?? [];
        $taxConfig['vat'] = $taxConfig['vat'] ?? [];
        $taxConfig['social_security'] = $taxConfig['social_security'] ?? [];
        
        return view('admin.tax-configuration.index', compact('taxConfig'));
    }

    public function clearConfigCache(Request $request)
    {
        try {
            Log::info('Admin User ID: ' . (Auth::id() ?? 'Unknown') . ' initiated config cache clear via admin panel.');
            Artisan::call('config:clear');
            Artisan::call('optimize:clear'); 
            return redirect()->route('admin.tax-configuration.index')->with('success', 'Configuration cache cleared successfully. Changes from config/tax_rates.php should now be reflected (server restart may sometimes be needed).');
        } catch (\Exception $e) {
            Log::error('Error clearing config cache by admin: ' . $e->getMessage());
            return redirect()->route('admin.tax-configuration.index')->with('error', 'Failed to clear configuration cache: ' . $e->getMessage());
        }
    }
}
