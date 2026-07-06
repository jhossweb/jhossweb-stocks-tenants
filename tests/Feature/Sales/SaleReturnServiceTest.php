<?php

use App\DTOs\Sales\SaleReturn\SaleReturnDTO;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\MovementsSale;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Services\Sales\SaleReturn\SaleReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);
    TransactionType::create(['trans_type_code' => 'DEVV', 'trans_type_description' => 'Devolucion Venta', 'trans_type_factor' => '1']);
    Brand::factory()->create();
    Category::factory()->create();
});

test('store creates sale order and movements', function () {
    $customer = Customer::factory()->create();

    $originalSale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $customer->id,
        'sls_total' => 116.00,
    ]);

    $product = Product::factory()->create();
    Warehouse::factory()->create(['id' => 1]);
    $stock = new Stock;
    $stock->stk_pro_code = $product->pro_code;
    $stock->stk_war_id = 1;
    $stock->stk_cant = 100;
    $stock->save();

    $originalSale->movements()->create([
        'mvs_number' => $originalSale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => '-1',
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => 1,
        'mvs_quantity' => 10,
        'mvs_unit_price' => 10,
        'mvs_unit_price_ref' => 10,
    ]);

    $dto = SaleReturnDTO::fromArray([
        'sls_number' => 'DEV-001',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => (string) $customer->id,
        'sls_valorcam' => '1.0',
        'sls_coin' => 'USD',
        'sls_reference' => $originalSale->sls_number,
        'sls_subtotal' => '100.00',
        'sls_exento' => '0.00',
        'sls_iva' => '16.00',
        'sls_total' => '116.00',
        'items' => [
            [
                'mvs_pro_code' => $product->pro_code,
                'mvs_pro_iva' => 16,
                'mvs_war_id' => 1,
                'mvs_quantity' => 2,
                'mvs_unit_price' => 10,
                'mvs_unit_price_ref' => 10,
            ],
        ],
    ]);

    $service = app(SaleReturnService::class);
    $result = $service->store($dto);

    expect($result)->toBeInstanceOf(SaleOrder::class);
    expect($result->sls_number)->toBe('DEV-001');
    expect($result->sls_trans_type_code)->toBe('DEVV');
    expect($result->sls_status)->toBe('paid');

    expect(MovementsSale::where('mvs_number', 'DEV-001')->count())->toBe(1);
});

test('store throws exception when original sale does not exist', function () {
    $customer = Customer::factory()->create();

    $dto = SaleReturnDTO::fromArray([
        'sls_number' => 'DEV-002',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => (string) $customer->id,
        'sls_valorcam' => '1.0',
        'sls_coin' => 'USD',
        'sls_reference' => 'NONEXISTENT',
        'sls_subtotal' => '100.00',
        'sls_exento' => '0.00',
        'sls_iva' => '16.00',
        'sls_total' => '116.00',
        'items' => [],
    ]);

    $service = app(SaleReturnService::class);

    expect(fn () => $service->store($dto))
        ->toThrow(RuntimeException::class, 'no existe');
});

test('store throws exception when quantity exceeds available', function () {
    $customer = Customer::factory()->create();

    $originalSale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $customer->id,
    ]);

    $product = Product::factory()->create();
    Warehouse::factory()->create(['id' => 1]);

    $originalSale->movements()->create([
        'mvs_number' => $originalSale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => '-1',
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => 1,
        'mvs_quantity' => 5,
        'mvs_unit_price' => 10,
        'mvs_unit_price_ref' => 10,
    ]);

    $dto = SaleReturnDTO::fromArray([
        'sls_number' => 'DEV-003',
        'sls_date' => '2026-06-15',
        'sls_customer_id' => (string) $customer->id,
        'sls_valorcam' => '1.0',
        'sls_coin' => 'USD',
        'sls_reference' => $originalSale->sls_number,
        'sls_subtotal' => '100.00',
        'sls_exento' => '0.00',
        'sls_iva' => '16.00',
        'sls_total' => '116.00',
        'items' => [
            [
                'mvs_pro_code' => $product->pro_code,
                'mvs_pro_iva' => 16,
                'mvs_war_id' => 1,
                'mvs_quantity' => 10,
                'mvs_unit_price' => 10,
                'mvs_unit_price_ref' => 10,
            ],
        ],
    ]);

    $service = app(SaleReturnService::class);

    expect(fn () => $service->store($dto))
        ->toThrow(RuntimeException::class, 'excede la disponible');
});

test('destroy removes sale order and movements', function () {
    $customer = Customer::factory()->create();
    $warehouse = Warehouse::factory()->create(['id' => 1]);
    $product = Product::factory()->create();

    $saleReturn = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $customer->id,
    ]);

    $movement = $saleReturn->movements()->create([
        'mvs_number' => $saleReturn->sls_number,
        'mvs_trans_type_code' => 'DEVV',
        'mvs_factor' => '1',
        'mvs_pro_code' => $product->pro_code,
        'mvs_pro_iva' => 0,
        'mvs_war_id' => $warehouse->id,
        'mvs_quantity' => 1,
        'mvs_unit_price' => 0,
        'mvs_unit_price_ref' => 0,
    ]);

    $service = app(SaleReturnService::class);
    $service->destroy($saleReturn);

    expect(SaleOrder::find($saleReturn->id))->toBeNull();
    expect(MovementsSale::find($movement->id))->toBeNull();
});
