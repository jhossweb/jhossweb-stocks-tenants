<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enum\Tenancy\BillingCycle;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('plan-####'),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'billing_cycle' => BillingCycle::Monthly->value,
            'price_usd' => fake()->randomFloat(2, 10, 300),
            'trial_days' => 14,
            'is_active' => true,
            'limits' => null,
            'features' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
