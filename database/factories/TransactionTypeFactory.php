<?php

namespace Database\Factories;

use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionType>
 */
class TransactionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trans_type_code' => fake()->unique()->text(6),
            'trans_type_description' => fake()->text(20),
            'trans_type_factor' => fake()->randomElement(['1', '-1']),
        ];
    }
}
