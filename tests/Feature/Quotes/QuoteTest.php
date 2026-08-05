<?php

use App\Enum\Inventary\ProType;
use App\Enum\Sales\QuoteStatus;
use App\Models\AccountsReceivable;
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

    TransactionType::create(['trans_type_code' => 'COT', 'trans_type_description' => 'Cotizacion', 'trans_type_factor' => '0', 'trans_last_number' => 1]);
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
});

function quotePayload(array $overrides = []): array
{
    $customer = Customer::first();
    $product = Product::first();
    $warehouse = Warehouse::where('id', 1)->first();

    return array_merge([
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
        'sls_subtotal' => 100.0000,
        'sls_exento' => 0.0000,
        'sls_iva' => 16.0000,
        'sls_total' => 116.0000,
        'items' => [
            [
                'mvs_pro_code' => $product->pro_code,
                'mvs_war_id' => $warehouse->id,
                'mvs_quantity' => 5,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ], $overrides);
}

test('index renders only cotizaciones (COT)', function () {
    SaleOrder::factory()->create([
        'sls_number' => 'COT-0001',
        'sls_trans_type_code' => 'COT',
        'sls_customer_id' => $this->customer->id,
        'sls_quote_status' => QuoteStatus::PENDING->value,
    ]);

    SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-0001',
        'sls_customer_id' => $this->customer->id,
    ]);

    $this->get(route('quotes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 1)
            ->where('quotes.data.0.sls_number', 'COT-0001')
        );
});

test('create renders the form with required data', function () {
    $this->get(route('quotes.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Quotes/Create')
            ->has('customers')
            ->has('products')
            ->has('warehouses')
            ->has('rates')
            ->has('latest_rate')
            ->has('coins')
            ->has('ivas')
        );
});

test('store generates automatic COT numbering', function () {
    $this->post(route('quotes.store'), quotePayload())
        ->assertSessionHasNoErrors();

    $this->post(route('quotes.store'), quotePayload())
        ->assertSessionHasNoErrors();

    $numbers = SaleOrder::where('sls_trans_type_code', 'COT')
        ->orderBy('sls_number')
        ->pluck('sls_number')
        ->all();

    expect($numbers)->toBe(['COT-0001', 'COT-0002']);
});

test('store does not create an account receivable nor reduce stock', function () {
    $this->post(route('quotes.store'), quotePayload())
        ->assertSessionHasNoErrors();

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    expect($quote->sls_quote_status)->toBe(QuoteStatus::PENDING->value);
    expect(AccountsReceivable::where('acr_sls_number', $quote->sls_number)->exists())->toBeFalse();
    expect(Stock::where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->value('stk_cant'))->toBe(100);
    expect((string) MovementsSale::where('mvs_number', $quote->sls_number)->first()->mvs_factor)->toBe('0');
});

test('store accepts a product without stock', function () {
    $noStockProduct = Product::factory()->create([
        'pro_iva' => '0',
    ]);

    PriceHistory::create([
        'price_pro_code' => $noStockProduct->pro_code,
        'price_utility' => 10,
        'price_usd' => 30.00,
        'price_date' => now(),
        'price_user_id' => $this->user->id,
    ]);

    CostHistory::create([
        'cost_pro_code' => $noStockProduct->pro_code,
        'cost_pro_cost' => 10.00,
        'cost_pro_cost_ref' => 10.00,
        'cost_valorcam' => 1.0,
        'cost_currency' => 'USD',
        'cost_date' => now(),
        'cost_user_id' => $this->user->id,
    ]);

    $payload = quotePayload([
        'sls_subtotal' => 30.0,
        'sls_exento' => 30.0,
        'sls_iva' => 0.0,
        'sls_total' => 30.0,
        'items' => [
            [
                'mvs_pro_code' => $noStockProduct->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 1,
                'mvs_unit_price' => 30.0000,
                'mvs_unit_price_ref' => 30.0000,
                'mvs_pro_iva' => 0,
            ],
        ],
    ]);

    $this->post(route('quotes.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(SaleOrder::where('sls_trans_type_code', 'COT')->count())->toBe(1);
});

test('store validates required fields', function () {
    $this->post(route('quotes.store'), [])
        ->assertSessionHasErrors(['sls_date', 'sls_customer_id', 'sls_valorcam', 'sls_total', 'items']);
});

test('convert creates FACV with stock, CxC and marks quote converted', function () {
    $this->post(route('quotes.store'), quotePayload())
        ->assertSessionHasNoErrors();

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    $this->post(route('quotes.convert', $quote->id), [
        'sls_number' => 'FACV-CONVERT-001',
    ])
        ->assertSessionHasNoErrors();

    $sale = SaleOrder::where('sls_number', 'FACV-CONVERT-001')->first();

    expect($sale)->not->toBeNull();
    expect($sale->sls_trans_type_code)->toBe('FACV');
    expect($sale->sls_reference)->toBe($quote->sls_number);
    expect($sale->sls_payment_condition)->toBe('cash');

    $quote->refresh();
    expect($quote->sls_quote_status)->toBe(QuoteStatus::CONVERTED->value);

    expect(AccountsReceivable::where('acr_sls_number', 'FACV-CONVERT-001')->exists())->toBeTrue();
    expect(Stock::where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->value('stk_cant'))->toBe(95);
});

test('convert rejects already converted quotes', function () {
    $this->post(route('quotes.store'), quotePayload());

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    $this->post(route('quotes.convert', $quote->id), ['sls_number' => 'FACV-ONE'])
        ->assertSessionHasNoErrors();

    $this->post(route('quotes.convert', $quote->id), ['sls_number' => 'FACV-TWO'])
        ->assertSessionHasErrors(['error']);
});

test('edit renders quote with movements', function () {
    $this->post(route('quotes.store'), quotePayload());

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    $this->get(route('quotes.edit', $quote->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Quotes/Edit')
            ->where('quote.sls_number', $quote->sls_number)
            ->has('quote.movements')
            ->has('quote_availability')
            ->where('quote_availability.ok', true)
        );
});

test('convert fails with insufficient stock without side effects', function () {
    Stock::where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->update(['stk_cant' => 3]);

    $this->post(route('quotes.store'), quotePayload());

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    $this->post(route('quotes.convert', $quote->id), [
        'sls_number' => 'FACV-NOSTOCK',
    ])
        ->assertSessionHasErrors(['error'])
        ->assertSessionHas('quote_availability');

    $availability = session('quote_availability');
    expect($availability['ok'])->toBeFalse();
    expect($availability['missing'][0]['pro_code'])->toBe($this->product->pro_code);
    expect((float) $availability['missing'][0]['required'])->toBe(5.0);
    expect((float) $availability['missing'][0]['available'])->toBe(3.0);
    expect((float) $availability['missing'][0]['shortage'])->toBe(2.0);

    expect(SaleOrder::where('sls_trans_type_code', 'FACV')->count())->toBe(0);
    expect(AccountsReceivable::count())->toBe(0);
    expect(Stock::where('stk_pro_code', $this->product->pro_code)
        ->where('stk_war_id', $this->warehouse->id)
        ->value('stk_cant'))->toBe(3);

    $quote->refresh();
    expect($quote->sls_quote_status)->toBe(QuoteStatus::PENDING->value);
});

test('convert fails when a composite component lacks stock', function () {
    $component = Product::factory()->create([
        'pro_type' => ProType::SIMPLE->value,
        'pro_iva' => '0',
    ]);

    $componentStock = new Stock;
    $componentStock->stk_pro_code = $component->pro_code;
    $componentStock->stk_war_id = $this->warehouse->id;
    $componentStock->stk_cant = 1;
    $componentStock->save();

    $compound = Product::factory()->create([
        'pro_type' => ProType::COMPUESTO->value,
        'pro_iva' => '0',
    ]);

    $compound->componentes()->attach($component->pro_code, [
        'cp_pro_und' => 'und',
        'cp_quantity' => 2,
    ]);

    $payload = quotePayload([
        'sls_subtotal' => 40.0,
        'sls_exento' => 40.0,
        'sls_iva' => 0.0,
        'sls_total' => 40.0,
        'items' => [
            [
                'mvs_pro_code' => $compound->pro_code,
                'mvs_war_id' => $this->warehouse->id,
                'mvs_quantity' => 1,
                'mvs_unit_price' => 40.0000,
                'mvs_unit_price_ref' => 40.0000,
                'mvs_pro_iva' => 0,
            ],
        ],
    ]);

    $this->post(route('quotes.store'), $payload)->assertSessionHasNoErrors();

    $quote = SaleOrder::where('sls_trans_type_code', 'COT')->first();

    $this->post(route('quotes.convert', $quote->id), [
        'sls_number' => 'FACV-COMP',
    ])
        ->assertSessionHasErrors(['error'])
        ->assertSessionHas('quote_availability');

    $availability = session('quote_availability');
    expect($availability['ok'])->toBeFalse();
    expect($availability['missing'][0]['pro_code'])->toBe($component->pro_code);
    expect((float) $availability['missing'][0]['required'])->toBe(2.0);
    expect((float) $availability['missing'][0]['available'])->toBe(1.0);
    expect($availability['missing'][0]['via_parent'])->toContain($compound->pro_name);

    expect(SaleOrder::where('sls_trans_type_code', 'FACV')->count())->toBe(0);
    expect(AccountsReceivable::count())->toBe(0);
});
