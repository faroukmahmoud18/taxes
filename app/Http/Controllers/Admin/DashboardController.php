<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription; // Though not directly used, good for context
use App\Models\User; // For other potential stats like userCount

class DashboardController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::withCount(['userSubscriptions' => function ($query) {
            $query->where('status', 'active')
                  ->where(function ($q) {
                      $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                  });
        }])->get();

        $totalEstimatedMrr = 0;
        foreach ($plans as $plan) {
            // Ensure price is treated as a float, especially if it's stored as decimal/string in DB
            $price = (float) $plan->price;
            $totalEstimatedMrr += $price * $plan->user_subscriptions_count;
        }

        $viewData = [
            'subscriptionPlansData' => $plans,
            'totalEstimatedMrr' => $totalEstimatedMrr,
            'userCount' => User::count(), // Example of another stat
        ];

        return view('admin.dashboard', $viewData);
    }
}
