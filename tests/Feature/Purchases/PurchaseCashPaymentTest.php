<?php

use App\Enum\Purchases\StatusPurchases;
use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FAC', 'trans_type_description' => 'Factura Compra', 'trans_type_factor' => '1']);

    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $this->warehouse = Warehouse::factory()->create(['id' => 1]);
    $this->supplier = Supplier::factory()->create();

    $this->product = Product::factory()->create([
        'pro_cat_id' => $category->id,
        'pro_bra_id' => $brand->id,
        'pro_iva' => '16',
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

    $this->purchaseData = [
        'invoice_number' => 'INV-CASH-001',
        'invoice_date' => now()->format('Y-m-d'),
        'invoice_supplier_id' => $this->supplier->id,
        'invoice_valorcam' => 1.0000,
        'invoice_coin' => 'USD',
        'invoice_subtotal' => 100.0000,
        'invoice_exento' => 0.0000,
        'invoice_iva' => 16.0000,
        'invoice_total' => 116.0000,
        'invoice_payment_condition' => 'cash',
        'invoice_credit_days' => 0,
        'invoice_due_date' => now()->format('Y-m-d'),
        'items' => [
            [
                'mov_pro_code' => $this->product->pro_code,
                'mov_war_id' => $this->warehouse->id,
                'mov_quantity' => 5,
                'mov_unit_cost' => 20.0000,
                'mov_unit_cost_ref' => 50.0000,
                'mov_pro_iva' => 16,
            ],
        ],
    ];
});

test('store a cash purchase redirects to create with payment_pending flash', function () {
    $this->post(route('purchases.store'), $this->purchaseData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.create'))
        ->assertSessionHas('payment_pending');

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first();

    expect($purchase->accountsPayable)->not->toBeNull();
    expect((float) $purchase->accountsPayable->acp_balance)->toBe(116.0);

    $flash = session('payment_pending');
    expect($flash['entity'])->toBe('purchase');
    expect($flash['acp_id'])->toBe($purchase->accountsPayable->id);
    expect((float) $flash['amount'])->toBe(116.0);
    expect($flash['currency'])->toBe('USD');
    expect($flash['supplier_id'])->toBe($this->supplier->id);
});

test('create page exposes payment_pending after a cash purchase', function () {
    $this->post(route('purchases.store'), $this->purchaseData);

    $this->get(route('purchases.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Purchases/Create')
            ->where('flash.payment_pending.entity', 'purchase')
            ->where('flash.payment_pending.acp_id', PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first()->accountsPayable->id)
        );
});

test('store a credit purchase redirects to index without payment_pending', function () {
    $creditData = array_merge($this->purchaseData, [
        'invoice_number' => 'INV-CREDIT-001',
        'invoice_payment_condition' => 'credit',
        'invoice_credit_days' => 30,
    ]);

    $this->post(route('purchases.store'), $creditData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'))
        ->assertSessionMissing('payment_pending');
});

test('a payment settles the pending cash purchase payable', function () {
    $this->post(route('purchases.store'), $this->purchaseData);

    $acp = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first()->accountsPayable;

    $this->post(route('payments.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'supplier_id' => $this->supplier->id,
        'bank_account_id' => $this->bankAccount->id,
        'payment_currency' => 'USD',
        'payment_rate' => 1.0000,
        'payment_method' => 'EFECTIVO',
        'payment_reference' => 'REF-CASH-001',
        'payment_description' => 'Pago de contado',
        'payments' => [
            ['acp_id' => $acp->id, 'amount' => 116.00],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-payable.index'));

    expect((float) $acp->fresh()->acp_balance)->toBe(0.0);
    expect($acp->fresh()->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->purchaseOrder->invoice_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->payments()->count())->toBe(1);
});

test('a payment in a different currency settles the full USD payable', function () {
    $this->post(route('purchases.store'), $this->purchaseData);

    $acp = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first()->accountsPayable;

    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $this->post(route('payments.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'supplier_id' => $this->supplier->id,
        'bank_account_id' => $bsBank->id,
        'payment_currency' => 'BS',
        'payment_rate' => 770.0000,
        'payment_method' => 'PAGO_MOVIL',
        'payment_reference' => 'REF-BS-001',
        'payment_description' => 'Pago en bolívares',
        'payments' => [
            ['acp_id' => $acp->id, 'amount' => 89320.0000],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-payable.index'));

    expect((float) $acp->fresh()->acp_balance)->toBe(0.0);
    expect($acp->fresh()->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->purchaseOrder->invoice_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->payments()->count())->toBe(1);
    expect((float) $acp->fresh()->payments()->first()->apy_amount)->toBe(116.0);
});

test('a payment on a date without an exact rate uses the most recent rate', function () {
    $this->post(route('purchases.store'), $this->purchaseData);

    $acp = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first()->accountsPayable;

    Rate::query()->delete();
    Rate::factory()->create([
        'rate_date' => now()->subDay()->format('Y-m-d'),
        'rate_usd' => 765.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);

    $this->post(route('payments.store'), [
        'payment_date' => now()->format('Y-m-d'),
        'supplier_id' => $this->supplier->id,
        'bank_account_id' => $this->bankAccount->id,
        'payment_currency' => 'USD',
        'payment_rate' => 765.0000,
        'payment_method' => 'EFECTIVO',
        'payment_reference' => 'REF-NORATE-001',
        'payment_description' => 'Pago sin tasa exacta del día',
        'payments' => [
            ['acp_id' => $acp->id, 'amount' => 116.00],
        ],
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-payable.index'));

    expect((float) $acp->fresh()->acp_balance)->toBe(0.0);
    expect($acp->fresh()->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->purchaseOrder->invoice_status)->toBe(StatusPurchases::PAID->value);
    expect($acp->fresh()->payments()->count())->toBe(1);
});
