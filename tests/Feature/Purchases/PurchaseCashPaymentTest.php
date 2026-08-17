<?php

use App\Enum\Purchases\StatusPurchases;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
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
        'rate_date' => now()->format('Y-m-d'),
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

function singlePurchasePaymentPayload(int $bankAccountId, float $amount, string $currency = 'USD', string $reference = 'REF-CASH-001'): array
{
    return [
        'payment_date' => now()->format('Y-m-d'),
        'payments' => [
            [
                'payment_currency' => $currency,
                'payment_rate' => 1.0000,
                'bank_account_id' => $bankAccountId,
                'payment_method' => 'EFECTIVO',
                'payment_reference' => $reference,
                'payment_description' => 'Pago de contado',
                'payment_amount' => $amount,
            ],
        ],
    ];
}

test('store a cash purchase without payment is rejected', function () {
    $this->post(route('purchases.store'), $this->purchaseData)
        ->assertSessionHasErrors([
            'payment.payment_date',
            'payment.payments',
        ]);

    expect(PurchaseOrder::where('invoice_number', 'INV-CASH-001')->exists())->toBeFalse();
    expect(AccountsPayable::count())->toBe(0);
});

test('store a cash purchase with a complete payment creates purchase and settles the payable', function () {
    $data = array_merge($this->purchaseData, [
        'payment' => singlePurchasePaymentPayload($this->bankAccount->id, 116.00),
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first();

    expect($purchase)->not->toBeNull();
    expect($purchase->accountsPayable)->not->toBeNull();
    expect((float) $purchase->accountsPayable->acp_balance)->toBe(0.0);
    expect($purchase->accountsPayable->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->invoice_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->accountsPayable->payments()->count())->toBe(1);
    expect((float) $purchase->accountsPayable->payments()->first()->apy_amount)->toBe(116.0);
});

test('store a cash purchase with a partial payment is rejected', function () {
    $data = array_merge($this->purchaseData, [
        'payment' => singlePurchasePaymentPayload($this->bankAccount->id, 50.00, 'USD', 'REF-PARTIAL-001'),
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasErrors('payment.payments');

    expect(PurchaseOrder::where('invoice_number', 'INV-CASH-001')->exists())->toBeFalse();
    expect(AccountsPayable::count())->toBe(0);
});

test('store a cash purchase with a payment that exceeds the total is rejected', function () {
    $data = array_merge($this->purchaseData, [
        'payment' => singlePurchasePaymentPayload($this->bankAccount->id, 150.00, 'USD', 'REF-EXCESS-001'),
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasErrors('payment.payments');

    expect(PurchaseOrder::where('invoice_number', 'INV-CASH-001')->exists())->toBeFalse();
    expect(AccountsPayable::count())->toBe(0);
});

test('store a cash purchase paid in a different currency settles the payable', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->purchaseData, [
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'payments' => [
                [
                    'payment_currency' => 'BS',
                    'payment_rate' => 770.0000,
                    'bank_account_id' => $bsBank->id,
                    'payment_method' => 'PAGO_MOVIL',
                    'payment_reference' => 'REF-BS-001',
                    'payment_description' => 'Pago en bolívares',
                    'payment_amount' => 89320.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first();

    expect((float) $purchase->accountsPayable->acp_balance)->toBe(0.0);
    expect($purchase->accountsPayable->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->invoice_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->accountsPayable->payments()->count())->toBe(1);
    expect((float) $purchase->accountsPayable->payments()->first()->apy_amount)->toBe(116.0);
});

test('store a cash purchase with multiple payments in different currencies settles the payable', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 780.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->purchaseData, [
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'payments' => [
                [
                    'payment_currency' => 'USD',
                    'payment_rate' => 1.0000,
                    'bank_account_id' => $this->bankAccount->id,
                    'payment_method' => 'EFECTIVO',
                    'payment_reference' => 'REF-USD-001',
                    'payment_description' => 'Abono en efectivo',
                    'payment_amount' => 60.0000,
                ],
                [
                    'payment_currency' => 'BS',
                    'payment_rate' => 780.0000,
                    'bank_account_id' => $bsBank->id,
                    'payment_method' => 'PAGO_MOVIL',
                    'payment_reference' => 'REF-BS-001',
                    'payment_description' => 'Abono por pago móvil',
                    'payment_amount' => 43680.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first();

    expect((float) $purchase->accountsPayable->acp_balance)->toBe(0.0);
    expect($purchase->accountsPayable->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->accountsPayable->payments()->count())->toBe(2);
});

test('store a credit purchase creates a pending payable without payment', function () {
    $creditData = array_merge($this->purchaseData, [
        'invoice_number' => 'INV-CREDIT-001',
        'invoice_payment_condition' => 'credit',
        'invoice_credit_days' => 30,
    ]);

    $this->post(route('purchases.store'), $creditData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'))
        ->assertSessionMissing('payment_pending');

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CREDIT-001')->first();

    expect((float) $purchase->accountsPayable->acp_balance)->toBe(116.0);
    expect($purchase->accountsPayable->acp_status)->toBe(StatusPurchases::PENDING->value);
    expect($purchase->accountsPayable->payments()->count())->toBe(0);
});

test('store a cash purchase on a date without an exact rate still settles using the most recent rate', function () {
    $purchaseDate = now()->subDay()->format('Y-m-d');

    Rate::query()->delete();
    Rate::factory()->create([
        'rate_date' => $purchaseDate,
        'rate_usd' => 765.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);

    $data = array_merge($this->purchaseData, [
        'invoice_date' => now()->format('Y-m-d'),
        'payment' => [
            'payment_date' => now()->addDay()->format('Y-m-d'),
            'payments' => [
                [
                    'payment_currency' => 'USD',
                    'payment_rate' => 765.0000,
                    'bank_account_id' => $this->bankAccount->id,
                    'payment_method' => 'EFECTIVO',
                    'payment_reference' => 'REF-NORATE-001',
                    'payment_description' => 'Pago sin tasa exacta del día',
                    'payment_amount' => 116.00,
                ],
            ],
        ],
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-001')->first();

    expect((float) $purchase->accountsPayable->acp_balance)->toBe(0.0);
    expect($purchase->accountsPayable->acp_status)->toBe(StatusPurchases::PAID->value);
    expect($purchase->accountsPayable->payments()->count())->toBe(1);
});

test('payments store still settles a pending cash purchase payable', function () {
    $this->post(route('purchases.store'), array_merge($this->purchaseData, [
        'invoice_number' => 'INV-CREDIT-001',
        'invoice_payment_condition' => 'credit',
        'invoice_credit_days' => 30,
    ]));

    $acp = PurchaseOrder::where('invoice_number', 'INV-CREDIT-001')->first()->accountsPayable;

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

test('store a cash purchase registers the bank movement on the payment date, not the invoice date', function () {
    $paymentDate = now()->format('Y-m-d');
    $invoiceDate = now()->subDay()->format('Y-m-d');

    $data = array_merge($this->purchaseData, [
        'invoice_number' => 'INV-CASH-DATE-001',
        'invoice_date' => $invoiceDate,
        'payment' => singlePurchasePaymentPayload($this->bankAccount->id, 116.00, 'USD', 'REF-DATE-001'),
    ]);

    $this->post(route('purchases.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('purchases.index'));

    $purchase = PurchaseOrder::where('invoice_number', 'INV-CASH-DATE-001')->first();
    $acp = $purchase->accountsPayable;

    expect((float) $acp->acp_balance)->toBe(0.0);
    expect($purchase->invoice_date->format('Y-m-d'))->toBe($invoiceDate);

    $bankTx = BankTransaction::where('btr_reference', 'REF-DATE-001')->first();
    expect($bankTx)->not->toBeNull();
    expect($bankTx->btr_transaction_date->format('Y-m-d'))->toBe($paymentDate);
    expect($bankTx->btr_transaction_date->format('Y-m-d'))->not->toBe($invoiceDate);
});
