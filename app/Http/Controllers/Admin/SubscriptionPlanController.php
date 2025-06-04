<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Log; // Added Log facade
// Remove: use Illuminate\Http\Request;
// Remove: use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest; // Added
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest; // Added

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subscription_plans = SubscriptionPlan::latest()->paginate(10);
        return view('admin.subscription-plans.index', compact('subscription_plans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionPlanRequest $request) // Changed to Form Request
    {
        $validatedData = $request->validated();

        $plan = new SubscriptionPlan();
        $plan->price = $validatedData['price'];
        $plan->paypal_plan_id = $validatedData['paypal_plan_id'] ?? null;

        $plan->setTranslation('name', 'en', $validatedData['name']['en']);
        $plan->setTranslation('name', 'de', $validatedData['name']['de']);
        $plan->setTranslation('name', 'ar', $validatedData['name']['ar']);

        if (isset($validatedData['features'])) {
            $plan->setTranslation('features', 'en', $validatedData['features']['en'] ?? '');
            $plan->setTranslation('features', 'de', $validatedData['features']['de'] ?? '');
            $plan->setTranslation('features', 'ar', $validatedData['features']['ar'] ?? '');
        }
        
        $plan->save();

        return redirect()->route('admin.subscription-plans.index')->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', ['subscription_plan' => $subscriptionPlan]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', ['subscription_plan' => $subscriptionPlan]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan) // Changed to Form Request
    {
        $validatedData = $request->validated();
        // Log::debug('Validated data in update SubscriptionPlan:', $validatedData); // REMOVED DEBUG LINE
        
        $subscriptionPlan->price = $validatedData['price'];
        $subscriptionPlan->paypal_plan_id = $validatedData['paypal_plan_id'] ?? null;

        $subscriptionPlan->setTranslation('name', 'en', $validatedData['name']['en']);
        $subscriptionPlan->setTranslation('name', 'de', $validatedData['name']['de']);
        $subscriptionPlan->setTranslation('name', 'ar', $validatedData['name']['ar']);
        
        // Handle 'features'
        $featuresData = $validatedData['features'] ?? []; // Default to empty array if 'features' not in validated data
        $allSubmittedFeaturesAreEffectivelyEmpty = true;

        foreach (['en', 'de', 'ar'] as $locale) {
            $featureValue = $featuresData[$locale] ?? null;
            // If $featuresData was initially empty (because 'features' was not in $validatedData),
            // $featureValue will be null here for all locales.
            // If $featuresData had locale keys, $featureValue will be that or null.

            // Set the translation for the locale. An empty string will effectively clear it for that locale.
            // If $featureValue was truly null (locale not submitted or submitted as null), it becomes empty string.
            $subscriptionPlan->setTranslation('features', $locale, $featureValue ?? '');

            if (!empty($featureValue)) { // A non-empty string for any locale means features are not "all empty"
                $allSubmittedFeaturesAreEffectivelyEmpty = false;
            }
        }

        if ($allSubmittedFeaturesAreEffectivelyEmpty) {
            // If, after processing all locales, no locale had actual content
            // (i.e., all were missing from input, or submitted as null/empty string),
            // then clear out the entire features json attribute.
            $subscriptionPlan->forgetAllTranslations('features');
        }
        // Note: If 'features' was not in $validatedData, $featuresData defaults to [],
        // $featureValue will be null for all locales, $allSubmittedFeaturesAreEffectivelyEmpty will be true,
        // and forgetAllTranslations will be called. This covers the "omitted features key" case.

        $subscriptionPlan->save();

        return redirect()->route('admin.subscription-plans.index')->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();
        return redirect()->route('admin.subscription-plans.index')->with('success', 'Subscription plan deleted successfully.');
    }
}
