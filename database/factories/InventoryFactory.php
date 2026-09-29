<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inv_number' => 'AJ-'.fake()->unique()->numerify('#####'),
            'inv_date' => now()->toDateString(),
            'inv_trans_type_code' => $this->invTransactionTypeCode(),
            'inv_notes' => null,
            'inv_status' => 'completed',
        ];
    }

    public function forAdjustment(): static
    {
        return $this->state(fn (): array => [
            'inv_trans_type_code' => 'INV',
        ]);
    }

    private function invTransactionTypeCode(): string
    {
        $code = 'INV';

        if (! TransactionType::query()->where('trans_type_code', $code)->exists()) {
            TransactionType::factory()->create([
                'trans_type_code' => $code,
                'trans_type_description' => 'Ajuste de inventario',
                'trans_type_factor' => '-1',
            ]);
        }

        return $code;
    }
}
