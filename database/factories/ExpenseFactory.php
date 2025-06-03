<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ExpenseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence,
            'amount' => $this->faker->randomFloat(2, 5, 200),
            'expense_date' => Carbon::instance($this->faker->dateTimeBetween('-1 year', 'now')),
            // Add other fields if necessary, e.g., 'category_id'
        ];
    }
}
