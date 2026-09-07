<?php

use App\Enum\Sales\StatusSales;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Rate;
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

it('cobra dos facturas de la misma moneda con movimientos en varias monedas (FIFO desacoplado)', function () {
    $saleA = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => 'USD',
        'sls_total' => 100.0,
        'sls_subtotal' => 100.0,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $saleB = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => 'USD',
        'sls_total' => 200.0,
        'sls_subtotal' => 200.0,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $acrA = $saleA->accountsReceivable;
    $acrB = $saleB->accountsReceivable;

    $bankCop = BankAccount::factory()->create([
        'bank_currency' => 'COP',
        'bank_balance' => 10000000,
    ]);

    $bankUsd = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 10000000,
    ]);

    $payload = [
        'payment_date' => now()->toDateString(),
        'customer_id' => $this->customer->id,
        'payments' => [
            ['acr_id' => $acrA->id, 'amount' => 100.0],
            ['acr_id' => $acrB->id, 'amount' => 200.0],
        ],
        'bank_movements' => [
            [
                'bank_account_id' => $bankCop->id,
                'currency' => 'COP',
                'amount' => 320000.0,
                'method' => 'TRANSFERENCIA',
                'reference' => 'REF-COP',
                'description' => 'Abono COP',
            ],
            [
                'bank_account_id' => $bankUsd->id,
                'currency' => 'USD',
                'amount' => 200.0,
                'method' => 'TRANSFERENCIA',
                'reference' => 'REF-USD',
                'description' => 'Abono USD',
            ],
        ],
    ];

    $this->post(route('receipts.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('accounts-receivable.index'));

    $acrA->refresh();
    $acrB->refresh();

    expect((float) $acrA->acr_balance)->toBe(0.0);
    expect($acrA->acr_status)->toBe(StatusSales::PAID->value);
    expect((float) $acrB->acr_balance)->toBe(0.0);
    expect($acrB->acr_status)->toBe(StatusSales::PAID->value);

    // One bank transaction per movement, in each movement's currency.
    $txCop = BankTransaction::where('btr_bank_account_id', $bankCop->id)
        ->where('btr_coin', 'COP')
        ->first();
    $txUsd = BankTransaction::where('btr_bank_account_id', $bankUsd->id)
        ->where('btr_coin', 'USD')
        ->first();

    expect($txCop)->not->toBeNull();
    expect($txUsd)->not->toBeNull();

    // The movement is recorded in the movement's own currency (amount applied converted to pay currency).
    expect((float) $txCop->btr_amount)->toBe(320000.0);
    expect((float) $txUsd->btr_amount)->toBe(200.0);

    // Bank balances update by the movement currency amounts.
    expect((float) $bankCop->fresh()->bank_balance)->toBe((float) 10000000 + 320000);
    expect((float) $bankUsd->fresh()->bank_balance)->toBe((float) 10000000 + 200);
});

it('lanza error al mezclar facturas de monedas distintas en una misma operación', function () {
    $saleUsd = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => 'USD',
        'sls_total' => 100.0,
        'sls_subtotal' => 100.0,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $saleCop = SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => 'COP',
        'sls_total' => 320000.0,
        'sls_subtotal' => 320000.0,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->toDateString(),
    ]);

    $acrUsd = $saleUsd->accountsReceivable;
    $acrCop = $saleCop->accountsReceivable;

    $bankUsd = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 10000000,
    ]);

    $payload = [
        'payment_date' => now()->toDateString(),
        'customer_id' => $this->customer->id,
        'payments' => [
            ['acr_id' => $acrUsd->id, 'amount' => 100.0],
            ['acr_id' => $acrCop->id, 'amount' => 320000.0],
        ],
        'bank_movements' => [
            [
                'bank_account_id' => $bankUsd->id,
                'currency' => 'COP',
                'amount' => 320000.0,
                'method' => 'TRANSFERENCIA',
                'reference' => 'REF-MIX',
                'description' => 'Pago mixto',
            ],
        ],
    ];

    $this->post(route('receipts.store'), $payload)
        ->assertSessionHasErrors();

    $acrUsd->refresh();
    $acrCop->refresh();

    expect((float) $acrUsd->acr_balance)->toBe(100.0);
    expect((float) $acrCop->acr_balance)->toBe(320000.0);
});
