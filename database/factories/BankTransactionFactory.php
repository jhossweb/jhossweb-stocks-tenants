<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'btr_bank_account_id' => BankAccount::factory(),
            'btr_type' => fake()->randomElement(['inflow', 'outflow']),
            'btr_amount' => fake()->randomFloat(4, 10, 5000),
            'btr_balance' => fake()->randomFloat(4, 5000, 100000),
            'btr_reference' => fake()->bothify('REF-#####'),
            'btr_description' => fake()->sentence(),
            'btr_valorcam' => fake()->randomFloat(4, 0, 1000),
        ];
    }
}
