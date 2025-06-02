<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

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

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('static-pages', StaticPageController::class)->parameters(['static-pages' => 'staticPage:slug']);
});
// ADMIN STATIC PAGES ROUTES END
