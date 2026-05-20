<?php

namespace Database\Factories;

use App\Enum\Tesoreria\TypePayment;
use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountPayments>
 */
class AccountPaymentsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'apy_bank_transaction_id' => BankTransaction::factory(),
            'apy_account_payable_id' => AccountsPayable::factory(),
            'apy_type' => fake()->randomElement(TypePayment::values()),
        ];
    }
}
