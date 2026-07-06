<?php

use App\Enum\Inventary\ProType;
use App\Enum\Sales\StatusSales;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\Customer;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Rate;
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
    Warehouse::factory()->create();

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

    Rate::factory()->create([
        'rate_date' => now(),
        'rate_usd' => 1.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);
});

test('index renders sales list', function () {
    SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Index')
            ->has('sales.data', 1)
        );
});

test('create renders the form with required data', function () {
    $this->get(route('sales.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Create')
            ->has('customers')
            ->has('products')
            ->has('warehouses')
            ->has('rates')
            ->has('latest_rate')
            ->has('coins')
            ->has('ivas')
        );
});

test('store creates a sale with valid data', function () {
    $data = [
        'sls_number' => 'SLS-001',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 5,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    expect(SaleOrder::where('sls_number', 'SLS-001')->exists())->toBeTrue();
    expect(SaleOrder::where('sls_number', 'SLS-001')->first()->sls_trans_type_code)->toBe('FACV');
    expect(SaleOrder::where('sls_number', 'SLS-001')->first()->sls_status)->toBe(StatusSales::PENDING->value);
});

test('store validates required fields', function () {
    $this->post(route('sales.store'), [])
        ->assertSessionHasErrors(['sls_number', 'sls_date', 'sls_customer_id', 'items']);
});

test('store validates unique sls_number', function () {
    SaleOrder::factory()->facv()->create([
        'sls_number' => 'SLS-EXISTING',
        'sls_customer_id' => $this->customer->id,
    ]);

    $data = [
        'sls_number' => 'SLS-EXISTING',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 5,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->post(route('sales.store'), $data)
        ->assertSessionHasErrors('sls_number');
});

test('store validates insufficient stock', function () {
    $data = [
        'sls_number' => 'SLS-NOSTOCK',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 999,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->post(route('sales.store'), $data)
        ->assertSessionHasErrors('items.0.mvs_quantity');
});

test('edit renders the edit form', function () {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('sales.edit', $sale->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Edit')
            ->has('sale')
            ->has('customers')
            ->has('products')
        );
});

test('update modifies a sale', function () {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $data = [
        'sls_number' => $sale->sls_number,
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 2.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 200.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 32.0000,
        'sls_total' => 232.0000,
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 3,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->put(route('sales.update', $sale->id), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    expect((float) $sale->fresh()->sls_valorcam)->toBe(2.0);
    expect((float) $sale->fresh()->sls_subtotal)->toBe(200.0);
});

test('update removes old items when not in request', function () {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $oldMovement = $sale->movements()->create([
        'mvs_number' => $sale->sls_number,
        'mvs_trans_type_code' => 'FACV',
        'mvs_factor' => '-1',
        'mvs_pro_code' => $this->product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => $this->warehouse->id,
        'mvs_quantity' => 5,
        'mvs_unit_price' => 20,
        'mvs_unit_price_ref' => 50,
    ]);

    $data = [
        'sls_number' => $sale->sls_number,
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 50.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 8.0000,
        'sls_total' => 58.0000,
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 2,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->put(route('sales.update', $sale->id), $data)
        ->assertSessionHasNoErrors();

    expect($sale->movements()->count())->toBe(1);
    expect($sale->movements()->first()->mvs_quantity)->toBe(2.0);
});
