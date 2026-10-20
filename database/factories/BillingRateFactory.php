<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BillingRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingRate>
 */
class BillingRateFactory extends Factory
{
    protected $model = BillingRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate_date' => fake()->unique()->date(),
            'rate_usd' => fake()->randomFloat(4, 1, 100),
            'rate_cop' => fake()->randomFloat(4, 1, 5000),
            'rate_eur' => fake()->randomFloat(4, 0.5, 2),
            'rate_cop_bs' => fake()->randomFloat(4, 0, 1),
        ];
    }
}
