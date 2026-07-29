<?php

namespace App\Repositories\Finance;

use App\Models\CashClosing;
use App\Models\SaleOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CashClosingRepository
{
    public function getCashSalesTotal(string $date): float
    {
        return (float) SaleOrder::query()
            ->where('sls_payment_condition', 'cash')
            ->where('sls_trans_type_code', 'FACV')
            ->where('sls_status', '!=', 'cancelled')
            ->whereDate('sls_date', '=', $date)
            ->sum('sls_total');
    }

    public function getCashReceivedTotal(string $date): float
    {
        $result = DB::table('receipts')
            ->join('bank_transactions', 'bank_transactions.id', '=', 'receipts.rec_bank_transaction_id')
            ->where('bank_transactions.btr_method', 'EFECTIVO')
            ->whereDate('bank_transactions.btr_transaction_date', '=', $date)
            ->where('receipts.rec_type', 'RECEIPT')
            ->sum('receipts.rec_amount');

        return (float) ($result ?? 0);
    }

    public function findClosingByDate(string $date): ?CashClosing
    {
        return CashClosing::query()
            ->with(['movements', 'closedBy:id,name'])
            ->where('cc_date', $date)
            ->first();
    }

    public function storeClosing(array $data, Collection $movements): CashClosing
    {
        return DB::transaction(function () use ($data, $movements) {
            $closing = CashClosing::create($data);

            if ($movements->isNotEmpty()) {
                $closing->movements()->createMany($movements->toArray());
            }

            return $closing;
        });
    }

    public function getHistory(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        $query = CashClosing::query()
            ->select([
                'id',
                'cc_date',
                'cc_opening_balance',
                'cc_counted_amount',
                'cc_difference',
                'cc_status',
                'cc_notes',
                'cc_closed_by',
                'cc_closed_at',
            ])
            ->with('closedBy:id,name');

        if ($dateFrom !== null) {
            $query->where('cc_date', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->where('cc_date', '<=', $dateTo);
        }

        if ($status !== null) {
            $query->where('cc_status', $status);
        }

        return $query->orderByDesc('cc_date')->paginate(15);
    }
}
