<?php

declare(strict_types=1);

namespace App\Services\Finance\Bank;

use App\DTOs\Finance\Bank\BankTransferData;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankTransfer;
use App\Models\User;
use App\Repositories\Finance\Bank\BankTransferRepository;
use App\Services\Settings\TenantAuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BankTransferService
{
    public function __construct(
        private readonly BankTransferRepository $repository,
        private readonly TenantAuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(BankTransfer $transfer): BankTransfer
    {
        return $this->repository->findWithRelations($transfer);
    }

    public function create(BankTransferData $data, User $actor): BankTransfer
    {
        return DB::transaction(function () use ($data, $actor): BankTransfer {
            $source = BankAccount::query()->lockForUpdate()->findOrFail($data->sourceBankAccountId);
            $destination = BankAccount::query()->lockForUpdate()->findOrFail($data->destinationBankAccountId);

            $this->assertValidAccounts($source, $destination, $data->amount);

            $coin = (string) $source->bank_currency;

            $transfer = BankTransfer::create([
                'bt_date' => $data->date,
                'bt_source_bank_account_id' => $source->id,
                'bt_destination_bank_account_id' => $destination->id,
                'bt_amount' => $data->amount,
                'bt_coin' => $coin,
                'bt_reference' => $data->reference,
                'bt_description' => $data->description,
                'bt_created_by' => $actor->id,
            ]);

            $transfer->bt_number = 'TRF-'.str_pad((string) $transfer->id, 4, '0', STR_PAD_LEFT);
            $transfer->save();

            $this->createMovement($transfer, $source, BankTransaction::TYPE_OUTFLOW, $data->amount, $coin, $data->date, "Transferencia a {$destination->bank_name}");
            $this->createMovement($transfer, $destination, BankTransaction::TYPE_INFLOW, $data->amount, $coin, $data->date, "Transferencia desde {$source->bank_name}");

            $this->audit->log('bank_transfer.created', $transfer, [], [
                'number' => $transfer->bt_number,
                'source_account_id' => $source->id,
                'destination_account_id' => $destination->id,
                'amount' => $data->amount,
                'coin' => $coin,
                'actor_id' => $actor->id,
            ]);

            return $transfer->refresh();
        });
    }

    public function void(BankTransfer $transfer, User $actor, string $reason): BankTransfer
    {
        return DB::transaction(function () use ($transfer, $actor, $reason): BankTransfer {
            $transfer = BankTransfer::query()->lockForUpdate()->findOrFail($transfer->id);

            if ($transfer->isVoided()) {
                throw new \DomainException('La transferencia ya está anulada.');
            }

            $source = BankAccount::query()->lockForUpdate()->findOrFail($transfer->bt_source_bank_account_id);
            $destination = BankAccount::query()->lockForUpdate()->findOrFail($transfer->bt_destination_bank_account_id);

            if ((float) $destination->bank_balance < (float) $transfer->bt_amount) {
                throw new \DomainException('No se puede anular: la cuenta destino no tiene saldo suficiente para revertir la transferencia.');
            }

            $reference = $transfer->bt_number.'-REV';

            $this->createMovement($transfer, $source, BankTransaction::TYPE_INFLOW, (float) $transfer->bt_amount, (string) $transfer->bt_coin, $transfer->bt_date->format('Y-m-d'), "Anulación transferencia {$transfer->bt_number}", $reference);
            $this->createMovement($transfer, $destination, BankTransaction::TYPE_OUTFLOW, (float) $transfer->bt_amount, (string) $transfer->bt_coin, $transfer->bt_date->format('Y-m-d'), "Anulación transferencia {$transfer->bt_number}", $reference);

            $transfer->forceFill([
                'bt_voided_at' => now(),
                'bt_voided_by' => $actor->id,
                'bt_void_reason' => $reason,
            ])->save();

            $this->audit->log('bank_transfer.voided', $transfer, [], [
                'number' => $transfer->bt_number,
                'reason' => $reason,
                'actor_id' => $actor->id,
            ]);

            return $transfer->refresh();
        });
    }

    private function assertValidAccounts(BankAccount $source, BankAccount $destination, float $amount): void
    {
        if ($source->id === $destination->id) {
            throw new \DomainException('La cuenta origen y destino deben ser diferentes.');
        }

        if (! $source->bank_status || ! $destination->bank_status) {
            throw new \DomainException('Solo se permiten transferencias entre cuentas activas.');
        }

        if (strtoupper((string) $source->bank_currency) !== strtoupper((string) $destination->bank_currency)) {
            throw new \DomainException('Las cuentas deben tener la misma moneda.');
        }

        if ($amount <= 0) {
            throw new \DomainException('El monto debe ser mayor que cero.');
        }

        if ((float) $source->bank_balance < $amount) {
            throw new \DomainException('La cuenta origen no tiene saldo suficiente.');
        }
    }

    private function createMovement(
        BankTransfer $transfer,
        BankAccount $account,
        string $type,
        float $amount,
        string $coin,
        string $date,
        string $description,
        ?string $reference = null,
    ): BankTransaction {
        $transaction = new BankTransaction([
            'btr_bank_account_id' => $account->id,
            'btr_type' => $type,
            'btr_amount' => $amount,
            'btr_balance' => 0,
            'btr_reference' => $reference ?? $transfer->bt_number,
            'btr_description' => $description,
            'btr_valorcam' => 1,
            'btr_diferencial' => 0,
            'btr_coin' => $coin,
            'btr_method' => 'TRANSFERENCIA',
            'btr_transaction_date' => $date,
        ]);

        $transaction->btr_transactionable()->associate($transfer);
        $transaction->save();

        return $transaction;
    }
}
