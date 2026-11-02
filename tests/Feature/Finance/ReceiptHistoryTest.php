<?php

use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Receipt;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FACV', 'trans_type_description' => 'Factura Venta', 'trans_type_factor' => '-1']);

    $this->customer = Customer::factory()->create();
    $this->bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 10000.00,
        'bank_status' => true,
    ]);

    $this->sale = SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-0001',
        'sls_customer_id' => $this->customer->id,
        'sls_coin' => 'USD',
        'sls_total' => 116.00,
        'sls_status' => 'paid',
    ]);

    $this->acr = AccountsReceivable::create([
        'acr_sls_number' => $this->sale->sls_number,
        'acr_total' => 116.00,
        'acr_balance' => 0.00,
        'acr_status' => 'paid',
    ]);

    $this->inflow = BankTransaction::create([
        'btr_bank_account_id' => $this->bankAccount->id,
        'btr_type' => 'inflow',
        'btr_amount' => 116.00,
        'btr_balance' => 0,
        'btr_reference' => 'REF-COBRO-001',
        'btr_description' => 'Cobro a cliente',
        'btr_valorcam' => 1.0000,
        'btr_diferencial' => 0,
        'btr_coin' => 'USD',
        'btr_method' => 'EFECTIVO',
        'btr_transaction_date' => now()->format('Y-m-d'),
    ]);

    $this->inflow->btr_transactionable()->associate($this->acr);
    $this->inflow->save();

    $this->receipt = Receipt::create([
        'rec_account_receivable_id' => $this->acr->id,
        'rec_bank_transaction_id' => $this->inflow->id,
        'rec_type' => 'RECEIPT',
        'rec_amount' => 116.00,
        'rec_diferencial' => 0,
    ]);
});

test('receipts index lists cobros with document, customer and bank', function () {
    $this->get(route('receipts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Receipts/Index')
            ->has('receipts.data', 1)
            ->where('receipts.data.0.id', $this->receipt->id)
            ->where('receipts.data.0.accounts_receivable.sale_order.sls_number', 'FACV-0001')
            ->where('receipts.data.0.accounts_receivable.sale_order.customer.clt_name', $this->customer->clt_name)
            ->where('receipts.data.0.banks', [$this->bankAccount->bank_name])
        );
});

test('receipts index filters by customer name', function () {
    $otherCustomer = Customer::factory()->create();
    $otherSale = SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-0002',
        'sls_customer_id' => $otherCustomer->id,
        'sls_coin' => 'USD',
        'sls_total' => 50.00,
        'sls_status' => 'paid',
    ]);
    $otherAcr = AccountsReceivable::create([
        'acr_sls_number' => $otherSale->sls_number,
        'acr_total' => 50.00,
        'acr_balance' => 0.00,
        'acr_status' => 'paid',
    ]);

    $this->get(route('receipts.index', ['search' => $this->customer->clt_name]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Receipts/Index')
            ->has('receipts.data', 1)
            ->where('receipts.data.0.accounts_receivable.sale_order.sls_number', 'FACV-0001')
        );

    expect($otherAcr->id)->toBeInt();
});

test('receipts show renders cobro detail without vuelto', function () {
    $this->get(route('receipts.show', $this->receipt->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Receipts/Show')
            ->where('receipt.id', $this->receipt->id)
            ->where('receipt.bank_transaction.btr_reference', 'REF-COBRO-001')
            ->where('vueltos', [])
        );
});

test('receipts show includes the vuelto outflow when present', function () {
    $vuelto = BankTransaction::create([
        'btr_bank_account_id' => $this->bankAccount->id,
        'btr_type' => 'outflow',
        'btr_amount' => 34.00,
        'btr_balance' => 0,
        'btr_reference' => 'REF-COBRO-001',
        'btr_description' => 'Vuelto a cliente',
        'btr_valorcam' => 1.0000,
        'btr_diferencial' => 0,
        'btr_coin' => 'USD',
        'btr_method' => 'EFECTIVO',
        'btr_transaction_date' => now()->format('Y-m-d'),
    ]);

    $vuelto->btr_transactionable()->associate($this->acr);
    $vuelto->save();

    $this->get(route('receipts.show', $this->receipt->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Receipts/Show')
            ->where('vueltos.0.id', $vuelto->id)
            ->where('vueltos.0.btr_type', 'outflow')
            ->where('vueltos.0.btr_description', 'Vuelto a cliente')
        );
});
