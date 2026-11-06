<?php

declare(strict_types=1);

use App\DTOs\Finance\Bank\BankTransferData;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankTransfer;
use App\Models\User;
use App\Services\Finance\Bank\BankTransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function transferAccounts(float $sourceBalance = 1000, float $destinationBalance = 100): array
{
    return [
        BankAccount::factory()->create([
            'bank_name' => 'Origen',
            'bank_currency' => 'USD',
            'bank_balance' => $sourceBalance,
            'bank_status' => true,
        ]),
        BankAccount::factory()->create([
            'bank_name' => 'Destino',
            'bank_currency' => 'USD',
            'bank_balance' => $destinationBalance,
            'bank_status' => true,
        ]),
    ];
}

it('registra una transferencia y actualiza los saldos', function (): void {
    [$source, $destination] = transferAccounts();
    $actor = User::factory()->create();

    $transfer = app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 250,
            'bt_reference' => 'REF-001',
            'bt_description' => 'Prueba',
        ]),
        $actor,
    );

    expect($transfer->bt_number)->toBe('TRF-0001')
        ->and((float) $source->refresh()->bank_balance)->toBe(750.0)
        ->and((float) $destination->refresh()->bank_balance)->toBe(350.0)
        ->and($transfer->transactions()->count())->toBe(2);

    $transactions = $transfer->transactions()->orderBy('id')->get();
    expect($transactions[0]->btr_type)->toBe(BankTransaction::TYPE_OUTFLOW)
        ->and($transactions[0]->btr_bank_account_id)->toBe($source->id)
        ->and($transactions[1]->btr_type)->toBe(BankTransaction::TYPE_INFLOW)
        ->and($transactions[1]->btr_bank_account_id)->toBe($destination->id);
});

it('rechaza transferencias entre monedas distintas', function (): void {
    [$source, $destination] = transferAccounts();
    $destination->update(['bank_currency' => 'BS']);

    expect(fn () => app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 100,
        ]),
        User::factory()->create(),
    ))->toThrow(DomainException::class);
});

it('rechaza transferencias a la misma cuenta', function (): void {
    [$source] = transferAccounts();

    expect(fn () => app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $source->id,
            'bt_amount' => 100,
        ]),
        User::factory()->create(),
    ))->toThrow(DomainException::class);
});

it('rechaza transferencias sin saldo suficiente', function (): void {
    [$source, $destination] = transferAccounts(sourceBalance: 50);

    expect(fn () => app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 100,
        ]),
        User::factory()->create(),
    ))->toThrow(DomainException::class);
});

it('anula una transferencia y restaura los saldos', function (): void {
    [$source, $destination] = transferAccounts();
    $actor = User::factory()->create();

    $transfer = app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 250,
        ]),
        $actor,
    );

    app(BankTransferService::class)->void($transfer, $actor, 'Error de captura');

    expect($transfer->refresh()->isVoided())->toBeTrue()
        ->and((float) $source->refresh()->bank_balance)->toBe(1000.0)
        ->and((float) $destination->refresh()->bank_balance)->toBe(100.0)
        ->and($transfer->transactions()->count())->toBe(4);
});

it('no anula dos veces la misma transferencia', function (): void {
    [$source, $destination] = transferAccounts();
    $actor = User::factory()->create();

    $transfer = app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 100,
        ]),
        $actor,
    );

    app(BankTransferService::class)->void($transfer, $actor, 'Motivo');

    expect(fn () => app(BankTransferService::class)->void($transfer, $actor, 'Motivo'))
        ->toThrow(DomainException::class);
});

it('registra y muestra una transferencia desde las rutas', function (): void {
    [$source, $destination] = transferAccounts();
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->post('/bank-transfers', [
        'bt_date' => now()->toDateString(),
        'bt_source_bank_account_id' => $source->id,
        'bt_destination_bank_account_id' => $destination->id,
        'bt_amount' => 100,
        'bt_reference' => 'HTTP-001',
    ]);

    $transfer = BankTransfer::query()->firstOrFail();

    $response->assertRedirect(route('bank-transfers.show', $transfer));

    $this->actingAs($user)
        ->get(route('bank-transfers.show', $transfer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Transfers/Show')
            ->where('transfer.bt_number', 'TRF-0001'));
});

it('muestra la transferencia en el análisis de movimientos bancarios', function (): void {
    [$source, $destination] = transferAccounts();
    $actor = User::factory()->create(['email_verified_at' => now()]);

    app(BankTransferService::class)->create(
        BankTransferData::fromArray([
            'bt_date' => now()->toDateString(),
            'bt_source_bank_account_id' => $source->id,
            'bt_destination_bank_account_id' => $destination->id,
            'bt_amount' => 150,
        ]),
        $actor,
    );

    $this->actingAs($actor)
        ->get('/bank-transaction-analysis')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/Bank/Analysis/Index')
            ->has('transactions.data', 2)
            ->where('transactions.data.0.btr_transactionable.bt_number', 'TRF-0001'));
});
