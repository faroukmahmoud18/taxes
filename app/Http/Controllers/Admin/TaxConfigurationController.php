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
        
        // Default structure lines removed as the new config/tax_rates.php has a different structure.
        // The view will be updated to display the raw new structure.
        
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
