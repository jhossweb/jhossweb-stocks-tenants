<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MovementsPurchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Brand::factory()->count(2)->create();
        Category::factory()->count(2)->create();
        Warehouse::factory()->count(2)->create();
        Product::factory()->count(100)->create();
        Supplier::factory()->count(10)->create();
        TransactionType::create(
            [
                'trans_type_code' => 'FAC',
                'trans_type_description' => 'FACTURA COMPRA',
                'trans_type_factor' => '1',
            ]
        );

        TransactionType::create(
            [
                'trans_type_code' => 'DEVC',
                'trans_type_description' => 'DEVOLUCION COMPRA',
                'trans_type_factor' => '-1',
            ]
        );

        $purchaseOrders = PurchaseOrder::factory()->count(10)->create();
        foreach ($purchaseOrders as $purchaseOrder) {
            MovementsPurchase::factory()->count(50)->create([
                'mov_invoice_number' => $purchaseOrder->invoice_number,
                'mov_invoice_trans_type_code' => $purchaseOrder->invoice_trans_type_code,
                'mov_factor' => fake()->randomElement(['1', '-1']),
                'mov_pro_code' => Product::inRandomOrder()->first()->pro_code,
                'mov_pro_iva' => fake()->numberBetween(0, 16),
                'mov_war_id' => Warehouse::inRandomOrder()->first()->id,
                'mov_quantity' => fake()->numberBetween(1, 100),
                'mov_unit_cost' => fake()->numberBetween(1, 100),
                'mov_unit_cost_ref' => fake()->numberBetween(1, 100),
            ]);
        }

    }
}
