<?php

use App\Enum\Inventary\ProType;
use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
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

function singlePaymentPayload(int $bankAccountId, float $amount, string $currency = 'USD', string $reference = 'REF-CASH-001'): array
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
                'payment_description' => 'Cobro de contado',
                'payment_amount' => $amount,
            ],
        ],
    ];
}

test('store a cash sale without payment is rejected', function () {
    $this->post(route('sales.store'), $this->saleData)
        ->assertSessionHasErrors([
            'payment.payment_date',
            'payment.payments',
        ]);

    expect(SaleOrder::where('sls_number', 'SLS-CASH-001')->exists())->toBeFalse();
    expect(AccountsReceivable::count())->toBe(0);
});

test('store a cash sale with a complete payment creates sale and settles the receivable', function () {
    $data = array_merge($this->saleData, [
        'payment' => singlePaymentPayload($this->bankAccount->id, 116.00),
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-001')->first();
    $acr = $sale->accountsReceivable;

    expect($acr)->not->toBeNull();
    expect((float) $acr->acr_balance)->toBe(0.0);
    expect($acr->acr_status)->toBe(StatusSales::PAID->value);
    expect($sale->sls_status)->toBe(StatusSales::PAID->value);
    expect($acr->payments()->count())->toBe(1);
    expect((float) $acr->payments()->first()->rec_amount)->toBe(116.0);
});

test('store a cash sale with a partial payment is rejected', function () {
    $data = array_merge($this->saleData, [
        'payment' => singlePaymentPayload($this->bankAccount->id, 50.00, 'USD', 'REF-PARTIAL-001'),
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasErrors('payment.payments');

    expect(SaleOrder::where('sls_number', 'SLS-CASH-001')->exists())->toBeFalse();
    expect(AccountsReceivable::count())->toBe(0);
});

test('store a cash sale paid in a different currency settles the receivable', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-BS-001',
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'payments' => [
                [
                    'payment_currency' => 'BS',
                    'payment_rate' => 770.0000,
                    'bank_account_id' => $bsBank->id,
                    'payment_method' => 'PAGO_MOVIL',
                    'payment_reference' => 'REF-BS-001',
                    'payment_description' => 'Cobro en bolívares',
                    'payment_amount' => 89320.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-BS-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->saleOrder->sls_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(1);
    expect((float) $acr->fresh()->payments()->first()->rec_amount)->toBe(116.0);
});

test('store a cash sale with multiple payments in different currencies settles the receivable', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 780.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-MIXED-001',
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

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-MIXED-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(2);
});

test('store a cash sale that exceeds the total registers the vuelto as an outflow', function () {
    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-VUELTO-001',
        'payment' => singlePaymentPayload($this->bankAccount->id, 150.00, 'USD', 'REF-VUELTO-001'),
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-VUELTO-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);

    $inflows = BankTransaction::where('btr_type', 'inflow')
        ->where('btr_transactionable_type', $acr->getMorphClass())
        ->where('btr_transactionable_id', $acr->id)
        ->get();

    $outflow = BankTransaction::where('btr_type', 'outflow')
        ->where('btr_transactionable_type', $acr->getMorphClass())
        ->where('btr_transactionable_id', $acr->id)
        ->first();

    expect($inflows)->toHaveCount(1);
    expect((float) $inflows->first()->btr_amount)->toBe(116.0);

    expect($outflow)->not->toBeNull();
    expect((float) $outflow->btr_amount)->toBe(34.0);
    expect($outflow->btr_description)->toBe('Vuelto a cliente');
    expect($outflow->btr_coin->value)->toBe('USD');
    expect((float) $acr->fresh()->payments()->first()->rec_amount)->toBe(116.0);
});

test('store a cash sale with a typed vuelto in a different currency uses the chosen bank', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-VUELTO-BS-001',
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'vueltos' => [
                [
                    'vuelto_amount' => 26180.0000,
                    'vuelto_currency' => 'BS',
                    'vuelto_bank_account_id' => $bsBank->id,
                ],
            ],
            'payments' => [
                [
                    'payment_currency' => 'USD',
                    'payment_rate' => 1.0000,
                    'bank_account_id' => $this->bankAccount->id,
                    'payment_method' => 'EFECTIVO',
                    'payment_reference' => 'REF-VUELTO-BS-001',
                    'payment_description' => 'Cobro de contado',
                    'payment_amount' => 150.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-VUELTO-BS-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);

    $outflow = BankTransaction::where('btr_type', 'outflow')
        ->where('btr_transactionable_type', $acr->getMorphClass())
        ->where('btr_transactionable_id', $acr->id)
        ->first();

    expect($outflow)->not->toBeNull();
    expect((float) $outflow->btr_amount)->toBe(26180.0);
    expect($outflow->btr_coin->value)->toBe('BS');
    expect($outflow->btr_bank_account_id)->toBe($bsBank->id);
    expect($outflow->btr_description)->toBe('Vuelto a cliente');
    expect((float) $bsBank->fresh()->bank_balance)->toBe(73820.0);
});

test('store a cash sale with a multi-currency vuelto registers an outflow per currency', function () {
    Rate::whereDate('rate_date', today())->update(['rate_usd' => 770.0000]);

    $bsBank = BankAccount::factory()->create([
        'bank_currency' => 'BS',
        'bank_balance' => 100000.00,
        'bank_status' => true,
    ]);

    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-VUELTO-MULTI-001',
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'vueltos' => [
                [
                    'vuelto_amount' => 20.0000,
                    'vuelto_currency' => 'USD',
                    'vuelto_bank_account_id' => $this->bankAccount->id,
                ],
                [
                    'vuelto_amount' => 10780.0000,
                    'vuelto_currency' => 'BS',
                    'vuelto_bank_account_id' => $bsBank->id,
                ],
            ],
            'payments' => [
                [
                    'payment_currency' => 'USD',
                    'payment_rate' => 1.0000,
                    'bank_account_id' => $this->bankAccount->id,
                    'payment_method' => 'EFECTIVO',
                    'payment_reference' => 'REF-VUELTO-MULTI-001',
                    'payment_description' => 'Cobro de contado',
                    'payment_amount' => 150.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-VUELTO-MULTI-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);

    $outflows = BankTransaction::where('btr_type', 'outflow')
        ->where('btr_transactionable_type', $acr->getMorphClass())
        ->where('btr_transactionable_id', $acr->id)
        ->get();

    expect($outflows)->toHaveCount(2);
    expect($outflows->pluck('btr_description')->unique()->all())->toBe(['Vuelto a cliente']);

    $usdVuelto = $outflows->first(fn ($tx) => $tx->btr_coin?->value === 'USD');
    $bsVuelto = $outflows->first(fn ($tx) => $tx->btr_coin?->value === 'BS');

    expect((float) $usdVuelto->btr_amount)->toBe(20.0);
    expect($usdVuelto->btr_bank_account_id)->toBe($this->bankAccount->id);
    expect((float) $bsVuelto->btr_amount)->toBe(10780.0);
    expect($bsVuelto->btr_bank_account_id)->toBe($bsBank->id);
    expect((float) $bsBank->fresh()->bank_balance)->toBe(89220.0);
});

test('store a cash sale with a typed vuelto greater than the excess is rejected', function () {
    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-VUELTO-EXCESO-001',
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'vueltos' => [
                [
                    'vuelto_amount' => 50.0000,
                    'vuelto_currency' => 'USD',
                    'vuelto_bank_account_id' => $this->bankAccount->id,
                ],
            ],
            'payments' => [
                [
                    'payment_currency' => 'USD',
                    'payment_rate' => 1.0000,
                    'bank_account_id' => $this->bankAccount->id,
                    'payment_method' => 'EFECTIVO',
                    'payment_reference' => 'REF-VUELTO-EXCESO-001',
                    'payment_description' => 'Cobro de contado',
                    'payment_amount' => 150.0000,
                ],
            ],
        ],
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasErrors('payment.payments');

    expect(SaleOrder::where('sls_number', 'SLS-CASH-VUELTO-EXCESO-001')->exists())->toBeFalse();
});

test('store a credit sale creates a pending receivable', function () {
    $creditData = array_merge($this->saleData, [
        'sls_number' => 'SLS-CREDIT-001',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
        'sls_due_date' => now()->addDays(30)->format('Y-m-d'),
    ]);

    $this->post(route('sales.store'), $creditData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CREDIT-001')->first();
    $acr = $sale->accountsReceivable;

    expect($acr)->not->toBeNull();
    expect((float) $acr->acr_balance)->toBe(116.0);
    expect($acr->acr_status)->toBe(StatusSales::PENDING->value);
    expect($acr->payments()->count())->toBe(0);
});

test('store a credit sale with an empty payment payload is saved without payment', function () {
    $creditData = array_merge($this->saleData, [
        'sls_number' => 'SLS-CREDIT-EMPTY-PAYMENT',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
        'sls_due_date' => now()->addDays(30)->format('Y-m-d'),
        'payment' => [
            'payment_date' => now()->format('Y-m-d'),
            'payments' => [],
        ],
    ]);

    $this->post(route('sales.store'), $creditData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CREDIT-EMPTY-PAYMENT')->first();
    $acr = $sale->accountsReceivable;

    expect($acr)->not->toBeNull();
    expect((float) $acr->acr_balance)->toBe(116.0);
    expect($acr->acr_status)->toBe(StatusSales::PENDING->value);
    expect($acr->payments()->count())->toBe(0);
});

test('store a cash sale on a date without an exact rate still settles using the most recent rate', function () {
    Rate::query()->delete();
    Rate::factory()->create([
        'rate_date' => now()->subDay()->format('Y-m-d'),
        'rate_usd' => 1.0000,
        'rate_cop' => 4000.0000,
        'rate_eur' => 0.9000,
    ]);

    $data = array_merge($this->saleData, [
        'payment' => singlePaymentPayload($this->bankAccount->id, 116.00, 'USD', 'REF-NORATE-001'),
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->fresh()->acr_balance)->toBe(0.0);
    expect($acr->fresh()->acr_status)->toBe(StatusSales::PAID->value);
    expect($acr->fresh()->payments()->count())->toBe(1);
});

test('store a cash sale registers the bank movement on the payment date, not the document date', function () {
    $paymentDate = now()->format('Y-m-d');
    $docDate = now()->subDay()->format('Y-m-d');

    $data = array_merge($this->saleData, [
        'sls_number' => 'SLS-CASH-DATE-001',
        'sls_date' => $docDate,
        'payment' => singlePaymentPayload($this->bankAccount->id, 116.00, 'USD', 'REF-DATE-001'),
    ]);

    $this->post(route('sales.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sales.index'));

    $sale = SaleOrder::where('sls_number', 'SLS-CASH-DATE-001')->first();
    $acr = $sale->accountsReceivable;

    expect((float) $acr->acr_balance)->toBe(0.0);
    expect($sale->sls_date->format('Y-m-d'))->toBe($docDate);

    $bankTx = BankTransaction::where('btr_reference', 'REF-DATE-001')->first();
    expect($bankTx)->not->toBeNull();
    expect($bankTx->btr_transaction_date->format('Y-m-d'))->toBe($paymentDate);
    expect($bankTx->btr_transaction_date->format('Y-m-d'))->not->toBe($docDate);
});
