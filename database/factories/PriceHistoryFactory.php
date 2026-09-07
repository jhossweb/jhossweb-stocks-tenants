<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price_pro_code' => Product::inRandomOrder()->first()->pro_code,
            'price_utility' => fake()->randomFloat(2, 5, 60),
            'price_usd' => fake()->randomFloat(4, 1, 500),
            'price_date' => fake()->date(),
            'price_user_id' => User::inRandomOrder()->first()->id ?? 1,
        ];
    }
}
