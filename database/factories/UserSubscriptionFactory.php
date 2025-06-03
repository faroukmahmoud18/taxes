<?php

namespace Database\Factories;

use App\Models\UserSubscription;
use App\Models\User;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $starts_at = Carbon::instance($this->faker->dateTimeBetween('-2 months', '-1 month'));
        $ends_at = null; // Default to ongoing, can be overridden in tests
        $status = $this->faker->randomElement(['active', 'cancelled', 'expired', 'pending']);

        if ($status === 'active') {
            $ends_at = Carbon::instance($starts_at)->addMonth(); // Or year, depending on plan interval
        } elseif ($status === 'cancelled' || $status === 'expired') {
            $ends_at = Carbon::instance($starts_at)->addMonth()->subDays(5); // Ended recently
        }


        return [
            'user_id' => User::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'paypal_subscription_id' => 'I-' . strtoupper($this->faker->unique()->bothify('????#############')),
            'status' => $status,
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
            'trial_ends_at' => null, // Can be set in tests if needed
            'cancelled_at' => ($status === 'cancelled' ? Carbon::instance($ends_at)->subDays(1) : null),
            'paypal_payload' => null, // Can be set in tests if needed
        ];
    }

    /**
     * Indicate that the subscription is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            $starts_at = $attributes['starts_at'] ?? Carbon::now()->subMonth();
            return [
                'status' => 'active',
                'starts_at' => $starts_at,
                'ends_at' => Carbon::instance($starts_at)->addMonth(), // Default active period
                'cancelled_at' => null,
            ];
        });
    }

    /**
     * Indicate that the subscription is cancelled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function cancelled(): Factory
    {
        return $this->state(function (array $attributes) {
            $starts_at = $attributes['starts_at'] ?? Carbon::now()->subMonths(2);
            $ends_at = Carbon::instance($starts_at)->addMonth(); // Typically ends after a billing cycle
            return [
                'status' => 'cancelled',
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
                'cancelled_at' => Carbon::instance($ends_at)->subDays(1), // Cancelled before it naturally ended
            ];
        });
    }

    /**
     * Indicate that the subscription has expired.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            $starts_at = $attributes['starts_at'] ?? Carbon::now()->subMonths(2);
            $ends_at = Carbon::instance($starts_at)->addMonth();
            return [
                'status' => 'expired',
                'starts_at' => $starts_at,
                'ends_at' => $ends_at, // ends_at is in the past
                'cancelled_at' => null,
            ];
        });
    }
}
