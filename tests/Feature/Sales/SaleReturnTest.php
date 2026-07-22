<?php

use App\Enum\Inventary\ProType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\Customer;
use App\Models\MovementsSale;
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

test('index renders returns list', function () {
    SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('sale-returns.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SaleReturns/Index')
            ->has('returns.data', 1)
        );
});

test('create renders the form with required data', function () {
    $this->get(route('sale-returns.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SaleReturns/Create')
            ->has('customers')
            ->has('products')
            ->has('warehouses')
            ->has('rates')
            ->has('latest_rate')
            ->has('coins')
            ->has('ivas')
        );
});

test('store creates a return with valid data', function () {
    $data = [
        'sls_number' => 'DEV-001',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_reference' => $this->originalSale->sls_number,
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 2,
                'mvs_unit_price' => 50.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->post(route('sale-returns.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sale-returns.index'));

    expect(SaleOrder::where('sls_number', 'DEV-001')->exists())->toBeTrue();
    expect(SaleOrder::where('sls_number', 'DEV-001')->first()->sls_trans_type_code)->toBe('DEVV');
});

test('store validates sls_reference exists', function () {
    $data = [
        'sls_number' => 'DEV-BADREF',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_reference' => 'NONEXISTENT',
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 2,
                'mvs_unit_price' => 50.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->post(route('sale-returns.store'), $data)
        ->assertSessionHasErrors('sls_reference');
});

test('store validates required fields', function () {
    $this->post(route('sale-returns.store'), [])
        ->assertSessionHasErrors(['sls_number', 'sls_date', 'sls_customer_id', 'items']);
});

test('edit renders the edit form', function () {
    $saleReturn = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('sale-returns.edit', $saleReturn->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('SaleReturns/Edit')
            ->has('saleReturn')
            ->has('customers')
            ->has('products')
        );
});

test('update modifies a return', function () {
    $saleReturn = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $data = [
        'sls_number' => $saleReturn->sls_number,
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 2.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 200.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 32.0000,
        'sls_total' => 232.0000,
        'sls_payment_condition' => 'cash',
        'sls_credit_days' => 0,
        'sls_due_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'mvs_pro_code' => $this->product->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 3,
                'mvs_unit_price' => 50.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];

    $this->put(route('sale-returns.update', $saleReturn->id), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sale-returns.index'));

    expect((float) $saleReturn->fresh()->sls_valorcam)->toBe(2.0);
});

test('destroy removes a return and its movements', function () {
    $saleReturn = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $saleReturn->movements()->create([
        'mvs_number' => $saleReturn->sls_number,
        'mvs_trans_type_code' => 'DEVV',
        'mvs_factor' => '1',
        'mvs_pro_code' => $this->product->pro_code,
        'mvs_pro_iva' => 16,
        'mvs_war_id' => $this->warehouse->id,
        'mvs_quantity' => 2,
        'mvs_unit_price' => 50,
        'mvs_unit_price_ref' => 50,
    ]);

    $this->delete(route('sale-returns.destroy', $saleReturn->id))
        ->assertRedirect(route('sale-returns.index'));

    expect(SaleOrder::find($saleReturn->id))->toBeNull();
    expect(MovementsSale::where('mvs_number', $saleReturn->sls_number)->exists())->toBeFalse();
});

test('show redirects to edit', function () {
    $saleReturn = SaleOrder::factory()->devv()->create([
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('sale-returns.show', $saleReturn->id))
        ->assertRedirect(route('sale-returns.edit', $saleReturn->id));
});
