<?php

use App\Enum\Inventary\ProType;
use App\Enum\Sales\StatusSales;
use App\Models\BankAccount;
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

    $this->bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 10000.00,
        'bank_status' => true,
    ]);

    $this->saleData = [
        'sls_number' => 'SLS-CASH-001',
        'sls_date' => now()->format('Y-m-d'),
        'sls_customer_id' => $this->customer->id,
        'sls_valorcam' => 1.0000,
        'sls_coin' => 'USD',
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
                'mvs_quantity' => 5,
                'mvs_unit_price' => 20.0000,
                'mvs_unit_price_ref' => 50.0000,
                'mvs_pro_iva' => 16,
            ],
        ],
    ];
});

test('store a cash sale redirects to create with payment_pending flash', function () {
    $this->post(route('sales.store'), $this->saleData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.create'))
        ->assertSessionHas('payment_pending');

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-001')->first();

    expect($sale->accountsReceivable)->not->toBeNull();
    expect((float) $sale->accountsReceivable->acr_balance)->toBe(116.0);

    $flash = session('payment_pending');
    expect($flash['entity'])->toBe('sale');
    expect($flash['acr_id'])->toBe($sale->accountsReceivable->id);
    expect((float) $flash['amount'])->toBe(116.0);
    expect($flash['currency'])->toBe('USD');
    expect($flash['customer_id'])->toBe($this->customer->id);
});

test('create page exposes payment_pending after a cash sale', function () {
    $this->post(route('sales.store'), $this->saleData);

    $this->get(route('sales.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sales/Create')
            ->where('flash.payment_pending.entity', 'sale')
            ->where('flash.payment_pending.acr_id', SaleOrder::where('sls_number', 'SLS-CASH-001')->first()->accountsReceivable->id)
        );
});

test('store a credit sale redirects to index without payment_pending', function () {
    $creditData = array_merge($this->saleData, [
        'sls_number' => 'SLS-CREDIT-001',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
    ]);

    $this->post(route('sales.store'), $creditData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'))
        ->assertSessionMissing('payment_pending');
});

test('a receipt pays the pending cash sale receivable', function () {
    $this->post(route('sales.store'), $this->saleData);

    $acr = SaleOrder::where('sls_number', 'SLS-CASH-001')->first()->accountsReceivable;

    $this->post(route('receipts.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'customer_id' => $this->customer->id,
        'bank_account_id' => $this->bankAccount->id,
        'payment_currency' => 'USD',
        'payment_rate' => 1.0000,
        'payment_method' => 'EFECTIVO',
        'payment_reference' => 'REF-CASH-001',
        'payment_description' => 'Cobro de contado',
        'payments' => [
            ['acr_id' => $acr->id, 'amount' => 116.00],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->saleOrder->sls_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(1);
});

test('a receipt in a different currency settles the full USD receivable', function () {
    $this->post(route('sales.store'), $this->saleData);

    $acr = SaleOrder::where('sls_number', 'SLS-CASH-001')->first()->accountsReceivable;

    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $this->post(route('receipts.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'customer_id' => $this->customer->id,
        'bank_account_id' => $bsBank->id,
        'payment_currency' => 'BS',
        'payment_rate' => 770.0000,
        'payment_method' => 'PAGO_MOVIL',
        'payment_reference' => 'REF-BS-001',
        'payment_description' => 'Cobro en bolívares',
        'payments' => [
            ['acr_id' => $acr->id, 'amount' => 89320.0000],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->saleOrder->sls_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(1);
    expect((float) $acr->fresh()->payments()->first()->rec_amount)->toBe(116.0);
});

test('a receipt on a date without an exact rate uses the most recent rate', function () {
    $this->post(route('sales.store'), $this->saleData);

    $acr = SaleOrder::where('sls_number', 'SLS-CASH-001')->first()->accountsReceivable;

    Rate::query()->delete();
    Rate::factory()->create([
        'rate_date' => now()->subDay()->format('Y-m-d'),
        'rate_usd' => 765.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);

    $this->post(route('receipts.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'customer_id' => $this->customer->id,
        'bank_account_id' => $this->bankAccount->id,
        'payment_currency' => 'USD',
        'payment_rate' => 765.0000,
        'payment_method' => 'EFECTIVO',
        'payment_reference' => 'REF-NORATE-001',
        'payment_description' => 'Cobro sin tasa exacta del día',
        'payments' => [
            ['acr_id' => $acr->id, 'amount' => 116.00],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->saleOrder->sls_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(1);
});
