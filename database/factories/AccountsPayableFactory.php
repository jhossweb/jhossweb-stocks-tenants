<?php

namespace Database\Factories;

use App\Enum\Purchases\StatusPurchases;
use App\Models\AccountsPayable;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountsPayable>
 */
class AccountsPayableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(4, 100, 10000);

        return [
            'acp_purchase_number' => function () {
                return PurchaseOrder::factory()->create()->invoice_number;
            },
            'acp_total' => $total,
            'acp_balance' => $total,
            'acp_status' => fake()->randomElement(StatusPurchases::values()),
        ];
    }
}
