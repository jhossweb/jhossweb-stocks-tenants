<?php

use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    TransactionType::create(['trans_type_code' => 'FAC', 'trans_type_description' => 'Factura Compra', 'trans_type_factor' => '1']);

    $this->supplier = Supplier::factory()->create();
    $this->bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 10000.00,
        'bank_status' => true,
    ]);

    $this->purchase = PurchaseOrder::factory()->create([
        'invoice_number' => 'INV-CASH-0001',
        'invoice_supplier_id' => $this->supplier->id,
        'invoice_coin' => 'USD',
        'invoice_total' => 116.00,
        'invoice_status' => 'paid',
    ]);

    $this->acp = AccountsPayable::create([
        'acp_purchase_number' => $this->purchase->invoice_number,
        'acp_total' => 116.00,
        'acp_balance' => 0.00,
        'acp_status' => 'paid',
    ]);

    $this->outflow = BankTransaction::create([
        'btr_bank_account_id' => $this->bankAccount->id,
        'btr_type' => 'outflow',
        'btr_amount' => 116.00,
        'btr_balance' => 0,
        'btr_reference' => 'REF-PAGO-001',
        'btr_description' => 'Pago a proveedor',
        'btr_valorcam' => 1.0000,
        'btr_diferencial' => 0,
        'btr_coin' => 'USD',
        'btr_method' => 'EFECTIVO',
        'btr_transaction_date' => now()->format('Y-m-d'),
    ]);

    $this->accountPayment = AccountPayments::create([
        'apy_bank_transaction_id' => $this->outflow->id,
        'apy_account_payable_id' => $this->acp->id,
        'apy_type' => 'PAYMENT',
        'apy_amount' => 116.00,
        'apy_diferencial' => 0,
    ]);
});

test('payments index lists payments with document, supplier and bank', function () {
    $this->get(route('payments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Payments/Index')
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $this->accountPayment->id)
            ->where('payments.data.0.account_payable.purchase_order.invoice_number', 'INV-CASH-0001')
            ->where('payments.data.0.account_payable.purchase_order.supplier.supplier_name', $this->supplier->supplier_name)
            ->where('payments.data.0.bank_transaction.bank_account.bank_name', $this->bankAccount->bank_name)
        );
});

test('payments index filters by supplier name', function () {
    $otherSupplier = Supplier::factory()->create();
    $otherPurchase = PurchaseOrder::factory()->create([
        'invoice_number' => 'INV-CASH-0002',
        'invoice_supplier_id' => $otherSupplier->id,
        'invoice_coin' => 'USD',
        'invoice_total' => 50.00,
        'invoice_status' => 'paid',
    ]);
    $otherAcp = AccountsPayable::create([
        'acp_purchase_number' => $otherPurchase->invoice_number,
        'acp_total' => 50.00,
        'acp_balance' => 0.00,
        'acp_status' => 'paid',
    ]);

    $this->get(route('payments.index', ['search' => $this->supplier->supplier_name]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Payments/Index')
            ->has('payments.data', 1)
            ->where('payments.data.0.account_payable.purchase_order.invoice_number', 'INV-CASH-0001')
        );

    expect($otherAcp->id)->toBeInt();
});

test('payments show renders payment detail', function () {
    $this->get(route('payments.show', $this->accountPayment->id))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Payments/Show')
            ->where('payment.id', $this->accountPayment->id)
            ->where('payment.bank_transaction.btr_reference', 'REF-PAGO-001')
            ->where('payment.account_payable.purchase_order.supplier.supplier_name', $this->supplier->supplier_name)
        );
});
