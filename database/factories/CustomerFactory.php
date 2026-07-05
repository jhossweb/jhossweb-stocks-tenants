<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'clt_name' => fake()->unique()->company(),
            'clt_address' => fake()->address(),
            'clt_phone' => fake()->unique()->phoneNumber(),
            'clt_email' => fake()->unique()->companyEmail(),
            'clt_website' => fake()->optional()->url(),
        ];
    }
}
