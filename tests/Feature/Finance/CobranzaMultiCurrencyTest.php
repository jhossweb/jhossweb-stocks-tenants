<?php

use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
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
        'bank_account_id' => $bank->id,
        'payment_currency' => $payCoin,
        'payment_rate' => 3.5,
        'payment_method' => 'TRANSFERENCIA',
        'payment_reference' => 'REF-MULTI',
        'payment_description' => 'Cobro multimoneda',
        'payments' => [
            [
                'acr_id' => $acr->id,
                'amount' => $payAmount,
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
