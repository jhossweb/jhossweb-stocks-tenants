<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['war_name' => 'Almacén Principal'],
            ['war_name' => 'Bodega Vidrio Crudo'],
            ['war_name' => 'Almacén Herrajes y Accesorios'],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}
