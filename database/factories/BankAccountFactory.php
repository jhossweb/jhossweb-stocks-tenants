<?php

namespace Database\Factories;

use App\Enum\Finanzas\TypeAccount;
use App\Enum\TypeCoin;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccount>
 */
class BankAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => fake()->company(),
            'bank_account_number' => fake()->unique()->numberBetween(1000000000, 9999999999),
            'bank_type' => fake()->randomElement(TypeAccount::values()),
            'bank_currency' => fake()->randomElement(TypeCoin::values()),
            'bank_status' => true,
            'bank_description' => fake()->sentence(),
            'bank_balance' => fake()->randomFloat(4, 0, 1000000),
        ];
    }
}
