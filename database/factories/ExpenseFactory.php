<?php

namespace Database\Factories;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exp_category_id' => ExpenseCategory::factory(),
            'exp_description' => fake()->sentence(),
            'exp_amount' => fake()->randomFloat(2, 10000, 5000000),
            'exp_date' => fake()->date(),
            'exp_status' => fake()->randomElement(ExpenseStatus::values()),
            'exp_coin' => TypeCoin::COP,
            'exp_valorcam' => 1,
        ];
    }
}
