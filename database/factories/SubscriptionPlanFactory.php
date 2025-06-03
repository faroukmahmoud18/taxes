<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['en' => $this->faker->words(2, true) . ' Plan'], // Store as translatable
            'price' => $this->faker->randomElement([9.99, 19.99, 29.99, 49.99]),
            'paypal_plan_id' => 'P-' . strtoupper($this->faker->unique()->bothify('???###???')),
            // Add other fields like 'features', 'interval' (e.g., 'month', 'year') if needed
            // 'interval' => $this->faker->randomElement(['month', 'year']),
            // 'interval_count' => 1,
            // 'currency' => 'EUR', // Assuming EUR from previous context
        ];
    }
}
