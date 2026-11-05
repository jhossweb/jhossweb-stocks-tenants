<?php

declare(strict_types=1);

use App\Enum\Expense\ExpenseStatus;
use App\Models\Expense;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Documents\DocumentLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TransactionType::create([
        'trans_type_code' => 'COT',
        'trans_type_description' => 'Cotización',
        'trans_type_factor' => 0,
    ]);
});

it('inactiva, reactiva y anula una cotización', function (): void {
    $actor = User::factory()->create();
    $quote = SaleOrder::factory()->create([
        'sls_trans_type_code' => 'COT',
        'sls_quote_status' => 'pending',
    ]);

    $service = app(DocumentLifecycleService::class);

    $service->deactivate($quote, $actor);
    expect($quote->refresh()->is_active)->toBeFalse();

    $service->activate($quote->refresh());
    expect($quote->refresh()->is_active)->toBeTrue();

    $service->void($quote->refresh(), $actor, 'Cliente desistió');
    $quote->refresh();

    expect($quote->voided_at)->not->toBeNull()
        ->and($quote->void_reason)->toBe('Cliente desistió')
        ->and($quote->is_active)->toBeFalse();
});

it('no permite modificar una cotización convertida', function (): void {
    $actor = User::factory()->create();
    $quote = SaleOrder::factory()->create([
        'sls_trans_type_code' => 'COT',
        'sls_quote_status' => 'converted',
    ]);

    expect(fn () => app(DocumentLifecycleService::class)->deactivate($quote, $actor))
        ->toThrow(DomainException::class);
});

it('no permite modificar un gasto pagado', function (): void {
    $actor = User::factory()->create();
    $expense = Expense::factory()->create([
        'exp_status' => ExpenseStatus::PAID->value,
    ]);

    expect(fn () => app(DocumentLifecycleService::class)->deactivate($expense, $actor))
        ->toThrow(DomainException::class);
});
