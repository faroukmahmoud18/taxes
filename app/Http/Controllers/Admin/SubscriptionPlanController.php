<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
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
        
        $subscriptionPlan->price = $validatedData['price'];
        $subscriptionPlan->paypal_plan_id = $validatedData['paypal_plan_id'] ?? null;

        $subscriptionPlan->setTranslation('name', 'en', $validatedData['name']['en']);
        $subscriptionPlan->setTranslation('name', 'de', $validatedData['name']['de']);
        $subscriptionPlan->setTranslation('name', 'ar', $validatedData['name']['ar']);
        
        if (isset($validatedData['features'])) {
            $subscriptionPlan->setTranslation('features', 'en', $validatedData['features']['en'] ?? '');
            $subscriptionPlan->setTranslation('features', 'de', $validatedData['features']['de'] ?? '');
            $subscriptionPlan->setTranslation('features', 'ar', $validatedData['features']['ar'] ?? '');
        } else {
            // If features are not present or empty, clear them for all locales
            $subscriptionPlan->forgetAllTranslations('features');
        }

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
