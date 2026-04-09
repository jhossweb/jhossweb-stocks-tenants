<?php

namespace Database\Factories;

use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rate>
 */
class RateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate_date' => fake()->date(),
            'rate_usd' => fake()->randomFloat(4, 1, 10),
            'rate_cop' => fake()->randomFloat(4, 1, 10),
            'rate_eur' => fake()->randomFloat(4, 1, 10),
        ];
    }
}
