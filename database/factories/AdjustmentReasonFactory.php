<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdjustmentReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdjustmentReason>
 */
class AdjustmentReasonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'adjr_code' => strtoupper(fake()->unique()->lexify('????')),
            'adjr_name' => fake()->unique()->words(2, true),
            'adjr_description' => fake()->sentence(),
        ];
    }
}
