<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSubscription;
use App\Models\Expense;
use App\Models\User; // Added User model import
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeSubscription = null;
        $recentExpenses = collect(); // Initialize as empty collection
        $totalExpensesThisMonth = 0;

        // Ensure $user is an instance of User model, otherwise relationships won't work
        if ($user instanceof User) {
            // Attempt to get active subscription
            // This assumes a relationship like `userSubscription()` or `subscriptions()` on the User model
            // that returns the relevant subscription(s).
            // If `UserSubscription` stores history, we might need to get the latest active one.
            // For example, if User hasMany UserSubscription:
            // Use the existing 'subscriptions' relationship and 'active' scope from UserSubscription model
            if (method_exists($user, 'subscriptions')) {
                $activeSubscription = $user->subscriptions()
                                            ->active()
                                            ->latest('created_at')
                                            ->first();
            }
            // If the relationship is direct, e.g., $user->subscription and it's a UserSubscription model
            // else if (isset($user->subscription) && $user->subscription instanceof UserSubscription && $user->subscription->status === 'active') {
            //    $activeSubscription = $user->subscription;
            // }


            // Get recent expenses (e.g., last 5)
            if (method_exists($user, 'expenses')) { // Check if expenses relationship exists
                $recentExpenses = $user->expenses()
                                    ->orderBy('expense_date', 'desc')
                                    ->limit(5)
                                    ->get();

                // Calculate total expenses this month
                $totalExpensesThisMonth = $user->expenses()
                                            ->whereYear('expense_date', Carbon::now()->year)
                                            ->whereMonth('expense_date', Carbon::now()->month)
                                            ->sum('amount');
            }
        }

        return view('dashboard', [
            'user' => $user, // Pass the user object to the view
            'activeSubscription' => $activeSubscription,
            'recentExpenses' => $recentExpenses,
            'totalExpensesThisMonth' => $totalExpensesThisMonth,
        ]);
    }
}
