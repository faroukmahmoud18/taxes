<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController; // Added DashboardController
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing'); // Optional: name the route

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Admin Routes for Subscription Plans
use App\Http\Controllers\Admin\SubscriptionPlanController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('subscription-plans', SubscriptionPlanController::class);
});

// ADMIN SUBSCRIPTION PLAN ROUTES START
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('subscription-plans', SubscriptionPlanController::class);
});
// ADMIN SUBSCRIPTION PLAN ROUTES END

// USER SUBSCRIPTION ROUTES START
use App\Http\Controllers\SubscriptionController;

Route::middleware(['auth'])->prefix('subscriptions')->name('subscriptions.')->group(function () {
    Route::get('/', [SubscriptionController::class, 'index'])->name('index'); // Show plans
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscribe'); // Initiate subscription
    Route::get('/success', [SubscriptionController::class, 'success'])->name('success'); // PayPal success callback
    Route::get('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel'); // PayPal cancel callback
});
// USER SUBSCRIPTION ROUTES END

// PAYPAL WEBHOOK ROUTE START
use App\Http\Controllers\PayPalWebhookController;
Route::post('/paypal/webhook', [PayPalWebhookController::class, 'handle'])->name('paypal.webhook');
// PAYPAL WEBHOOK ROUTE END

// ADMIN STATIC PAGES ROUTES START
use App\Http\Controllers\Admin\StaticPageController; // Ensure this is not duplicated if already present
use App\Http\Controllers\Admin\TaxConfigurationController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('static-pages', StaticPageController::class)->parameters(['static-pages' => 'staticPage:slug']);
});
// ADMIN STATIC PAGES ROUTES END
// ADMIN TAX CONFIGURATION ROUTE START
    Route::get('tax-configuration', [TaxConfigurationController::class, 'index'])->name('tax-configuration.index');
    Route::post('tax-configuration/clear-cache', [TaxConfigurationController::class, 'clearConfigCache'])->name('tax-configuration.clear-cache');
// ADMIN TAX CONFIGURATION ROUTE END

// FRONTEND STATIC PAGE ROUTE START
use App\Http\Controllers\PageController; // Ensure this is not duplicated if already present globally or in another block
Route::get('/pages/{staticPage:slug}', [PageController::class, 'show'])->name('pages.show');
// FRONTEND STATIC PAGE ROUTE END

// USER EXPENSE MANAGEMENT ROUTES START
use App\Http\Controllers\ExpenseController; // Ensure this is not duplicated if already present

Route::middleware(['auth'])->prefix('expenses')->name('expenses.')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('index');
    Route::get('/create', [ExpenseController::class, 'create'])->name('create');
    Route::post('/', [ExpenseController::class, 'store'])->name('store');
    Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show'); 
    Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
    Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
    Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    // Define the expenses.report route INSIDE the group
    Route::get('/report', [ExpenseController::class, 'report'])->name('report');
});
// USER EXPENSE MANAGEMENT ROUTES END

// TAX ESTIMATION ROUTES START
use App\Http\Controllers\TaxEstimationController;
Route::get('/tax-estimation', [TaxEstimationController::class, 'showForm'])->name('tax-estimation.show')->middleware('auth');
Route::post('/tax-estimation/calculate', [TaxEstimationController::class, 'calculate'])->name('tax-estimation.calculate')->middleware('auth');
// TAX ESTIMATION ROUTES END
