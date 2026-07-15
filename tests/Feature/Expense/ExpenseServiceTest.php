<?php

use App\Enum\Expense\ExpenseStatus;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Rate;
use App\Services\Expense\ExpenseService;
use App\Services\Finance\RateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->rateService = new RateService;
    $this->expenseService = new ExpenseService($this->rateService);
    $this->category = ExpenseCategory::factory()->create();
    $this->rate = Rate::factory()->create([
        'rate_date' => now()->format('Y-m-d'),
        'rate_usd' => 36.5,
        'rate_cop' => 4200,
        'rate_eur' => 40,
    ]);
});

test('create expense with default values', function () {
    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Alquiler mensual',
        'exp_amount' => 1000,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    expect($expense)->toBeInstanceOf(Expense::class);
    expect($expense->exp_paid_amount)->toBe('0.00');
    expect($expense->exp_remaining_balance)->toBe('1000.00');
    expect($expense->exp_status)->toBe(ExpenseStatus::PENDING);
});

test('register payment creates bank transaction and updates expense', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Servicios públicos',
        'exp_amount' => 500,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $transaction = $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 500,
        bankAccountId: $bankAccount->id,
        reference: 'REF-001',
        description: 'Pago total',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'TRANSFERENCIA',
        paymentDate: now()->format('Y-m-d'),
    );

    expect($transaction)->toBeInstanceOf(BankTransaction::class);
    expect($transaction->btr_type)->toBe('outflow');
    expect((float) $transaction->btr_amount)->toEqual(500.0);
    expect($transaction->btr_reference)->toBe('REF-001');

    $expense->refresh();
    expect($expense->exp_status)->toBe(ExpenseStatus::PAID);
    expect((float) $expense->exp_remaining_balance)->toEqual(0.0);
});

test('register partial payment updates balance', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Mantenimiento',
        'exp_amount' => 1000,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 300,
        bankAccountId: $bankAccount->id,
        reference: 'REF-002',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'EFECTIVO',
        paymentDate: now()->format('Y-m-d'),
    );

    $expense->refresh();
    expect($expense->exp_status)->toBe(ExpenseStatus::PENDING);
    expect((float) $expense->exp_remaining_balance)->toEqual(700.0);
    expect((float) $expense->exp_paid_amount)->toEqual(300.0);
});

test('cancel payment reverts expense and deletes transaction', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Combustible',
        'exp_amount' => 200,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $transaction = $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 200,
        bankAccountId: $bankAccount->id,
        reference: 'REF-003',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'EFECTIVO',
        paymentDate: now()->format('Y-m-d'),
    );

    $expense->refresh();
    expect($expense->exp_status)->toBe(ExpenseStatus::PAID);

    $this->expenseService->cancelPayment($expense, $transaction->id);

    $expense->refresh();
    expect($expense->exp_status)->toBe(ExpenseStatus::PENDING);
    expect((float) $expense->exp_remaining_balance)->toEqual(200.0);
    expect((float) $expense->exp_paid_amount)->toEqual(0.0);
    expect(BankTransaction::find($transaction->id))->toBeNull();
});

test('update expense validates amount against paid amount', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Test',
        'exp_amount' => 1000,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 500,
        bankAccountId: $bankAccount->id,
        reference: 'REF-004',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'EFECTIVO',
        paymentDate: now()->format('Y-m-d'),
    );

    $expense->refresh();

    $this->expectException(InvalidArgumentException::class);
    $this->expenseService->update($expense, [
        'exp_category_id' => $expense->exp_category_id,
        'exp_description' => 'Test',
        'exp_amount' => 200,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);
});

test('delete unpaid expense removes related transactions', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Test',
        'exp_amount' => 1000,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 500,
        bankAccountId: $bankAccount->id,
        reference: 'REF-005',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'EFECTIVO',
        paymentDate: now()->format('Y-m-d'),
    );

    $this->expenseService->delete($expense);

    expect(Expense::find($expense->id))->toBeNull();
    expect(BankTransaction::where('btr_transactionable_id', $expense->id)->count())->toBe(0);
});

test('cannot delete paid expense', function () {
    $bankAccount = BankAccount::factory()->create([
        'bank_currency' => 'USD',
        'bank_balance' => 50000,
    ]);

    $expense = $this->expenseService->create([
        'exp_category_id' => $this->category->id,
        'exp_description' => 'Test',
        'exp_amount' => 1000,
        'exp_date' => now()->format('Y-m-d'),
        'exp_coin' => 'USD',
    ]);

    $this->expenseService->registerPayment(
        expense: $expense,
        amountInBankCurrency: 1000,
        bankAccountId: $bankAccount->id,
        reference: 'REF-006',
        paymentCurrency: 'USD',
        paymentRate: 1,
        paymentMethod: 'EFECTIVO',
        paymentDate: now()->format('Y-m-d'),
    );

    $expense->refresh();

    $this->expectException(LogicException::class);
    $this->expenseService->delete($expense);
});
