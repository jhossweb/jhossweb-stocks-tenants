<?php

namespace Database\Seeders;

use App\Models\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $transactionTypes = [
            ['trans_type_code' => 'FAC', 'trans_type_description' => 'FACTURA COMPRA', 'trans_type_factor' => '1'],
            ['trans_type_code' => 'DEVC', 'trans_type_description' => 'DEVOLUCION COMPRA', 'trans_type_factor' => '-1'],
            ['trans_type_code' => 'FACV', 'trans_type_description' => 'FACTURA VENTA', 'trans_type_factor' => '-1'],
            ['trans_type_code' => 'DEVV', 'trans_type_description' => 'DEVOLUCION VENTA', 'trans_type_factor' => '1'],
            ['trans_type_code' => 'INV', 'trans_type_description' => 'AJUSTE INVENTARIO', 'trans_type_factor' => '-1'],
            ['trans_type_code' => 'COT', 'trans_type_description' => 'COTIZACION', 'trans_type_factor' => '0', 'trans_last_number' => 1],
        ];

        foreach ($transactionTypes as $transactionType) {
            TransactionType::create($transactionType);
        }
    }
}
