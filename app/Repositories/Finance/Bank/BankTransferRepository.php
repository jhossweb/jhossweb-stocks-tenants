<?php

declare(strict_types=1);

namespace App\Repositories\Finance\Bank;

use App\Models\BankTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BankTransferRepository
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = BankTransfer::query()
            ->with(['sourceAccount:id,bank_name', 'destinationAccount:id,bank_name', 'createdBy:id,name'])
            ->orderByDesc('bt_date')
            ->orderByDesc('id');

        if (! empty($filters['bank_account_id'])) {
            $query->where(function ($inner) use ($filters): void {
                $inner->where('bt_source_bank_account_id', $filters['bank_account_id'])
                    ->orWhere('bt_destination_bank_account_id', $filters['bank_account_id']);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('bt_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('bt_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $like = '%'.$filters['search'].'%';
            $query->where(function ($inner) use ($like): void {
                $inner->where('bt_number', 'like', $like)
                    ->orWhere('bt_reference', 'like', $like);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findWithRelations(BankTransfer $transfer): BankTransfer
    {
        return $transfer->load([
            'sourceAccount',
            'destinationAccount',
            'createdBy:id,name',
            'voidedBy:id,name',
            'transactions.bankAccount',
        ]);
    }
}
