<?php

namespace Database\Factories;

use App\Enum\Purchases\StatusPurchases;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => fake()->unique()->bothify('INV-#####'),
            'invoice_date' => fake()->date(),
            'invoice_trans_type_code' => \App\Models\TransactionType::inRandomOrder()->first()->trans_type_code,
            'invoice_supplier_id' => \App\Models\Supplier::inRandomOrder()->first()->id,
            'invoice_valorcam' => fake()->randomFloat(4, 1, 10),
            'invoice_coin' => fake()->randomElement(['USD', 'COP', 'EUR']),
            'invoice_subtotal' => fake()->randomFloat(4, 1, 10),
            'invoice_exento' => fake()->randomFloat(4, 1, 10),
            'invoice_iva' => fake()->randomFloat(4, 1, 10),

            'invoice_total' => fake()->randomFloat(4, 1, 10),
            'invoice_status' => fake()->randomElement(StatusPurchases::values()),
        ];
    }
}
