<?php

namespace Database\Seeders;

use App\Models\AdjustmentReason;
use Illuminate\Database\Seeder;

class AdjustmentReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'adjr_code' => 'MERMA',
                'adjr_name' => 'Merma',
                'adjr_description' => 'Pérdida de producto por merma',
            ],
            [
                'adjr_code' => 'DETERIORO',
                'adjr_name' => 'Deterioro',
                'adjr_description' => 'Producto dañado o en mal estado',
            ],
            [
                'adjr_code' => 'VENCIDO',
                'adjr_name' => 'Vencido',
                'adjr_description' => 'Producto vencido',
            ],
        ];

        foreach ($reasons as $reason) {
            AdjustmentReason::firstOrCreate(
                ['adjr_code' => $reason['adjr_code']],
                $reason,
            );
        }
    }
}
