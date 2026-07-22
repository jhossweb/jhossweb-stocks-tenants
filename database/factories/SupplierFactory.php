<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_name' => fake()->name(),
            'supplier_address' => fake()->address(),
            'supplier_phone' => fake()->phoneNumber(),
            'supplier_email' => fake()->email(),
            'supplier_website' => fake()->url(),
            'supplier_logo' => fake()->imageUrl(),
            'supplier_credit_days' => fake()->numberBetween(0, 90),
        ];
    }
}
