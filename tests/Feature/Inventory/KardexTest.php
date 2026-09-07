<?php

use App\Enum\Inventary\ProType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);

    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $this->warehouse = Warehouse::factory()->create(['id' => 1]);

    $this->customer = Customer::factory()->create();

    $this->product = Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
        'pro_type' => ProType::SIMPLE->value,
        'pro_iva' => '16',
    ]);
});

test('getMovements returns the sale movement for the selected period', function () {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_date' => now()->format('Y-m-d'),
    ]);

    $sale->movements()->create([
        'mvs_number' => $sale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => '-1',
        'mvs_pro_code' => $this->product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => $this->warehouse->id,
        'mvs_quantity' => 2,
        'mvs_unit_price' => 20,
        'mvs_unit_price_ref' => 50,
    ]);

    $response = $this->getJson(sprintf(
        '/api/kardex/%s/movements?warehouse_id=%d&year=%d&month=%d',
        $this->product->pro_code,
        $this->warehouse->id,
        (int) now()->year,
        (int) now()->month
    ));

    $response->assertOk();

    $data = $response->json();

    expect((float) $data['initial_balance'])->toBe(0.0);
    expect($data['movements'])->toHaveCount(1);
    expect($data['movements'][0]['origen'])->toBe('venta');
    expect($data['movements'][0]['documento'])->toBe($sale->sls_number);
    expect((float) $data['movements'][0]['factor'])->toBe(-1.0);
    expect((float) $data['movements'][0]['salida'])->toBe(2.0);
});
