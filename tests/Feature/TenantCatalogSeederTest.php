<?php

use App\Models\AdjustmentReason;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Rate;
use App\Models\TransactionType;
use App\Models\Warehouse;
use Database\Seeders\TenantCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('siembra el catálogo mínimo completo en un tenant vacío', function () {
    (new TenantCatalogSeeder)->run();

    expect(TransactionType::count())->toBe(6)
        ->and(AdjustmentReason::count())->toBe(3)
        ->and(ExpenseCategory::count())->toBe(15)
        ->and(Warehouse::count())->toBe(1)
        ->and(Category::count())->toBe(1)
        ->and(Brand::count())->toBe(1);

    expect(Warehouse::sole()->war_name)->toBe('Almacén Principal')
        ->and(Category::sole()->cat_name)->toBe('Indefinida')
        ->and(Brand::sole()->bra_name)->toBe('Indefinida');
});

test('es idempotente: correrlo dos veces no duplica nada', function () {
    (new TenantCatalogSeeder)->run();

    $before = [
        TransactionType::count(),
        AdjustmentReason::count(),
        ExpenseCategory::count(),
        Warehouse::count(),
        Category::count(),
        Brand::count(),
    ];

    (new TenantCatalogSeeder)->run();

    expect([
        TransactionType::count(),
        AdjustmentReason::count(),
        ExpenseCategory::count(),
        Warehouse::count(),
        Category::count(),
        Brand::count(),
    ])->toBe($before);
});

test('no pisa el foliador ya emitido de un tipo de transacción', function () {
    TransactionType::create([
        'trans_type_code' => 'FAC',
        'trans_type_description' => 'FACTURA COMPRA',
        'trans_type_factor' => '1',
        'trans_last_number' => 157,
    ]);

    (new TenantCatalogSeeder)->run();

    expect(TransactionType::where('trans_type_code', 'FAC')->sole()->trans_last_number)->toBe(157)
        ->and(TransactionType::count())->toBe(6);
});

test('no agrega Indefinida si el tenant ya tiene su propio catálogo', function () {
    Category::factory()->count(9)->create();
    Brand::factory()->count(8)->create();

    (new TenantCatalogSeeder)->run();

    expect(Category::count())->toBe(9)
        ->and(Brand::count())->toBe(8)
        ->and(Category::where('cat_name', 'Indefinida')->exists())->toBeFalse()
        ->and(Brand::where('bra_name', 'Indefinida')->exists())->toBeFalse();
});

test('no siembra tasas: la tasa la carga el usuario', function () {
    (new TenantCatalogSeeder)->run();

    expect(Rate::count())->toBe(0);
});

test('no siembra los catálogos demo de Vidrialum', function () {
    (new TenantCatalogSeeder)->run();

    expect(Brand::where('bra_name', 'Pilkington')->exists())->toBeFalse()
        ->and(Category::where('cat_name', 'Vidrio Templado')->exists())->toBeFalse()
        ->and(Warehouse::where('war_name', 'Bodega Vidrio Crudo')->exists())->toBeFalse();
});

test('deja lo necesario para registrar un ajuste de inventario', function () {
    (new TenantCatalogSeeder)->run();

    expect(TransactionType::where('trans_type_code', 'INV')->sole()->trans_type_factor)->toBe('-1')
        ->and(Warehouse::where('war_name', 'Almacén Principal')->exists())->toBeTrue()
        ->and(AdjustmentReason::whereIn('adjr_code', ['MERMA', 'DETERIORO', 'VENCIDO'])->count())->toBe(3);
});
