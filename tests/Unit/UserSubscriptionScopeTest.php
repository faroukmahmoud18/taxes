<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase; // Laravel's base TestCase for unit/feature tests

class UserSubscriptionScopeTest extends TestCase // Ensure it extends Laravel's TestCase
{
    use RefreshDatabase;

    public function test_active_scope_returns_only_active_subscriptions_with_future_ends_at(): void
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // 1. Active subscription, ends_at in future
        $activeSubFutureEnd = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active',
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        // 2. Active subscription, ends_at is null (ongoing)
        $activeSubNullEnd = UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active',
            'ends_at' => null,
        ]);

        // 3. Active subscription, but ends_at in past (should NOT be returned by scope)
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'active',
            'ends_at' => Carbon::now()->subDay(),
        ]);

        // 4. Cancelled subscription, ends_at in future (should NOT be returned)
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'cancelled',
            'ends_at' => Carbon::now()->addMonth(),
        ]);

        // 5. Expired subscription (should NOT be returned)
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'expired',
            'ends_at' => Carbon::now()->subMonth(),
        ]);

        // 6. Pending subscription (should NOT be returned)
        UserSubscription::factory()->for($user)->for($plan)->create([
            'status' => 'pending_approval',
            'ends_at' => Carbon::now()->addMonth(),
        ]);


        $activeSubscriptionsFromScope = UserSubscription::active()->get();

        $this->assertCount(2, $activeSubscriptionsFromScope, 'Should find 2 active subscriptions.');
        $this->assertTrue(
            $activeSubscriptionsFromScope->contains($activeSubFutureEnd),
            'Active subscription with future ends_at not found.'
        );
        $this->assertTrue(
            $activeSubscriptionsFromScope->contains($activeSubNullEnd),
            'Active subscription with null ends_at not found.'
        );
    }
}
