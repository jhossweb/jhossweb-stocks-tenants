<?php

use App\Enum\Inventary\ProType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\Customer;
use App\Models\MovementsSale;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);
    TransactionType::create(['trans_type_code' => 'DEVV', 'trans_type_description' => 'Devolucion Venta', 'trans_type_factor' => '1']);

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

    PriceHistory::create([
        'price_pro_code' => $this->product->pro_code,
        'price_utility' => 20,
        'price_usd' => 50.00,
        'price_date' => now(),
        'price_user_id' => $this->user->id,
    ]);

    CostHistory::create([
        'cost_pro_code' => $this->product->pro_code,
        'cost_pro_cost' => 20.00,
        'cost_pro_cost_ref' => 20.00,
        'cost_valorcam' => 1.0,
        'cost_currency' => 'USD',
        'cost_date' => now(),
        'cost_user_id' => $this->user->id,
    ]);

    $stock = new Stock;
    $stock->stk_pro_code = $this->product->pro_code;
    $stock->stk_war_id = $this->warehouse->id;
    $stock->stk_cant = 100;
    $stock->save();

    $this->originalSale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->originalSale->movements()->create([
        'mvs_number' => $this->originalSale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => '-1',
        'mvs_pro_code' => $this->product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => $this->warehouse->id,
        'mvs_quantity' => 10,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);
});

test('getUnpaidSales returns customer unpaid invoices', function () {
    $this->get("/api/customers/{$this->customer->id}/unpaid-sales")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['sls_number' => $this->originalSale->sls_number]);
});

test('getUnpaidSales returns empty for customer without sales', function () {
    $otherCustomer = Customer::factory()->create();

    $this->get("/api/customers/{$otherCustomer->id}/unpaid-sales")
        ->assertOk()
        ->assertJsonCount(0);
});

test('getSaleOrderByNumber returns a sale', function () {
    $this->get("/api/sale-orders/by-number/{$this->originalSale->sls_number}")
        ->assertOk()
        ->assertJsonFragment(['sls_number' => $this->originalSale->sls_number])
        ->assertJsonStructure(['sls_number', 'customer', 'movements']);
});

test('getSaleOrderByNumber returns 404 for nonexistent number', function () {
    $this->get('/api/sale-orders/by-number/NONEXISTENT')
        ->assertNotFound()
        ->assertJsonFragment(['error' => 'Factura de venta no encontrada']);
});

test('getSaleOrderByNumber ignores DEVV transactions', function () {
    $devvSale = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get("/api/sale-orders/by-number/{$devvSale->sls_number}")
        ->assertNotFound();
});

test('getSaleOrderItems returns items with available quantity', function () {
    $this->get("/api/sale-orders/{$this->originalSale->id}/items")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['mvs_pro_code' => $this->product->pro_code]);
});

test('getSaleOrderItems excludes fully returned items', function () {
    $returnMov = MovementsSale::where('mvs_number', $this->originalSale->sls_number)
        ->where('mvs_pro_code', $this->product->pro_code)
        ->first();

    SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_reference' => $this->originalSale->sls_number,
        'sls_number' => 'DEV-FULL',
    ])->movements()->create([
        'mvs_number' => 'DEV-FULL',
        'mvs_trans_type_code' => 'DEVV',
        'mvs_factor' => '1',
        'mvs_pro_code' => $this->product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => $this->warehouse->id,
        'mvs_quantity' => 10,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);

    $this->get("/api/sale-orders/{$this->originalSale->id}/items")
        ->assertOk()
        ->assertJsonCount(0);
});

test('getSaleOrderItems respects exclude_return_id', function () {
    $this->get("/api/sale-orders/{$this->originalSale->id}/items?exclude_return_id=999")
        ->assertOk()
        ->assertJsonCount(1);
});
