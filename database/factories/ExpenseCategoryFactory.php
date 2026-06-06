<?php

namespace Database\Factories;

use App\Enum\Expense\ExpenseType;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExpenseCategory>
 */
class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exc_name' => fake()->word(),
            'exc_type' => fake()->randomElement(ExpenseType::values()),
            'exc_description' => fake()->sentence(),
        ];
    }
}
