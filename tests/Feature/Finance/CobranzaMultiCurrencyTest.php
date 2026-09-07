<?php

use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\Receipt;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    TransactionType::create([
        'trans_type_code' => 'FACV',
        'trans_type_description' => 'Factura Venta',
        'trans_type_factor' => '-1',
    ]);

    $this->customer = Customer::factory()->create();

    Rate::factory()->create([
        'rate_date' => now()->toDateString(),
        'rate_usd' => 795.0,
        'rate_cop' => 3200.0,
        'rate_eur' => 895.0,
        'rate_cop_bs' => 3.5,
    ]);
});

it('registra cobranza multimoneda COP↔BS sin errores (factura en :docCoin, cobro en :payCoin)', function (
    string $docCoin,
    string $payCoin,
    float $total,
    float $payAmount,
) {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => $docCoin,
        'sls_total' => $total,
        'sls_subtotal' => $total,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $acr = $sale->accountsReceivable;

    expect((float) $acr->acr_balance)->toBe($total);

    $bank = BankAccount::factory()->create([
        'bank_currency' => $payCoin,
        'bank_balance' => 10000000,
    ]);

    $payload = [
        'payment_date' => now()->toDateString(),
        'customer_id' => $this->customer->id,
        'payments' => [
            [
                'acr_id' => $acr->id,
                'amount' => $total,
            ],
        ],
        'bank_movements' => [
            [
                'bank_account_id' => $bank->id,
                'currency' => $payCoin,
                'amount' => $payAmount,
                'method' => 'TRANSFERENCIA',
                'reference' => 'REF-MULTI',
                'description' => 'Cobro multimoneda',
            ],
        ],
    ];

    $this->post(route('receipts.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    $acr->refresh();

    expect(Receipt::where('rec_bank_transaction_id', $acr->payments()->first()->rec_bank_transaction_id)->exists())->toBeTrue();
    expect((float) $acr->acr_balance)->toBe(0.0);
    expect($acr->acr_status)->toBe(StatusSales::PAID->value);
    expect(AccountsReceivable::where('id', $acr->id)->value('acr_status'))->toBe(StatusSales::PAID->value);
})->with([
    'COP factura / BS cobro' => ['COP', 'BS', 350000.0, 100000.0],
    'BS factura / COP cobro' => ['BS', 'COP', 100000.0, 350000.0],
]);

it('registra el abono bancario en la moneda del pago y actualiza el saldo del banco', function (
    string $docCoin,
    string $payCoin,
    float $total,
    float $payAmount,
) {
    $sale = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => $docCoin,
        'sls_total' => $total,
        'sls_subtotal' => $total,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $acr = $sale->accountsReceivable;

    $bank = BankAccount::factory()->create([
        'bank_currency' => $payCoin,
        'bank_balance' => 10000000,
    ]);

    $payload = [
        'payment_date' => now()->toDateString(),
        'customer_id' => $this->customer->id,
        'payments' => [
            [
                'acr_id' => $acr->id,
                'amount' => $total,
            ],
        ],
        'bank_movements' => [
            [
                'bank_account_id' => $bank->id,
                'currency' => $payCoin,
                'amount' => $payAmount,
                'method' => 'TRANSFERENCIA',
                'reference' => 'REF-MULTI-BAL',
                'description' => 'Cobro multimoneda',
            ],
        ],
    ];

    $this->post(route('receipts.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    $bankTx = BankTransaction::where('btr_transactionable_id', $acr->id)
        ->where('btr_transactionable_type', $acr->getMorphClass())
        ->first();

    expect($bankTx)->not->toBeNull();
    expect($bankTx->btr_type)->toBe('inflow');
    expect($bankTx->btr_coin->value)->toBe($payCoin);
    expect((float) $bankTx->btr_amount)->toBe($payAmount);
    expect((float) $bank->fresh()->bank_balance)->toBe(10000000 + $payAmount);
})->with([
    'COP factura / BS cobro' => ['COP', 'BS', 350000.0, 100000.0],
    'BS factura / COP cobro' => ['BS', 'COP', 100000.0, 350000.0],
]);
