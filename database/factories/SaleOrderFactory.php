<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleOrder>
 */
class SaleOrderFactory extends Factory
{
    protected $model = SaleOrder::class;

    public function definition(): array
    {
        return [
            'sls_number' => fake()->unique()->numerify('SLS-#####'),
            'sls_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'sls_trans_type_code' => TransactionType::inRandomOrder()->first()->trans_type_code ?? 'FACV',
            'sls_customer_id' => Customer::factory(),
            'sls_valorcam' => fake()->randomFloat(4, 1, 100),
            'sls_coin' => fake()->randomElement(['USD', 'COP', 'BS', 'EUR']),
            'sls_subtotal' => fake()->randomFloat(4, 10, 1000),
            'sls_exento' => fake()->randomFloat(4, 0, 100),
            'sls_iva' => fake()->randomFloat(4, 0, 200),
            'sls_total' => fake()->randomFloat(4, 10, 1300),
            'sls_status' => 'pending',
            'sls_payment_condition' => 'cash',
            'sls_credit_days' => 0,
            'sls_due_date' => fn (array $attrs) => $attrs['sls_date'],
        ];
    }

    public function facv(): static
    {
        return $this->state(fn (array $attributes) => [
            'sls_trans_type_code' => 'FACV',
        ]);
    }

    public function devv(): static
    {
        return $this->state(fn (array $attributes) => [
            'sls_trans_type_code' => 'DEVV',
        ]);
    }
}
