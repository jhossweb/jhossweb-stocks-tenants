<?php

namespace App\Repositories\Purchases\AccountsPayable;

use App\Enum\Purchases\StatusPurchases;
use App\Models\AccountsPayable;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountsPayableRepository
{
    public function pendingWithFilters(?string $search = null, ?string $status = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = AccountsPayable::with(['purchaseOrder.supplier', 'payments.bankTransaction'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->status($status);
        }

        if ($search) {
            $query->whereHas('purchaseOrder', function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function (Builder $sq) use ($search) {
                        $sq->where('supplier_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Resumen consolidado de CxP agrupado por proveedor y moneda, con
     * antigüedad de saldos y el detalle de los documentos pendientes.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function summaryBySupplier(?string $search = null): \Illuminate\Support\Collection
    {
        $accounts = $this->basePendingQuery()->get();

        $accounts->each(fn ($account) => $account->purchaseOrder?->append('days_remaining'));

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $accounts = $accounts->filter(
                fn ($account) => $this->matchesSupplierSearch($account, $needle)
            );
        }

        return $accounts
            ->groupBy(fn ($account) => ($account->purchaseOrder?->supplier?->id ?? 0).'|'.($account->purchaseOrder?->invoice_coin ?? 'USD'))
            ->map(fn (Collection $group) => $this->buildSupplierSummary($group))
            ->sortBy('supplier.supplier_name')
            ->values();
    }

    public function getSupplierPending(Supplier $supplier): Collection
    {
        return $this->basePendingQuery()
            ->where('purchase_orders.invoice_supplier_id', $supplier->id)
            ->get();
    }

    private function basePendingQuery(): Builder
    {
        $table = (new AccountsPayable)->getTable();

        return AccountsPayable::query()
            ->with(['purchaseOrder.supplier', 'purchaseOrder.transactionType'])
            ->join('purchase_orders', 'purchase_orders.invoice_number', '=', $table.'.acp_purchase_number')
            ->select($table.'.*')
            ->whereIn($table.'.acp_status', [StatusPurchases::PENDING->value, StatusPurchases::PARTIAL->value])
            ->orderBy('purchase_orders.invoice_due_date');
    }

    private function matchesSupplierSearch($account, string $needle): bool
    {
        $supplier = $account->purchaseOrder?->supplier;

        return str_contains(mb_strtolower((string) $account->acp_purchase_number), $needle)
            || str_contains(mb_strtolower((string) $supplier?->supplier_name), $needle);
    }

    /**
     * @param  Collection<int, AccountsPayable>  $group
     * @return array<string, mixed>
     */
    private function buildSupplierSummary(Collection $group): array
    {
        $first = $group->first();
        $supplier = $first->purchaseOrder?->supplier;
        $coin = $first->purchaseOrder?->invoice_coin ?? 'USD';

        $aging = [
            'current' => 0.0,
            'days_1_30' => 0.0,
            'days_31_60' => 0.0,
            'days_61_90' => 0.0,
            'days_over_90' => 0.0,
        ];

        $details = [];
        $oldestDueDate = null;

        foreach ($group as $account) {
            $balance = round((float) $account->acp_balance, 4);
            $daysRemaining = (int) ($account->purchaseOrder?->days_remaining ?? 0);

            $aging[$this->agingBucket($daysRemaining)] += $balance;

            $dueDate = $account->purchaseOrder?->invoice_due_date
                ?? $account->purchaseOrder?->invoice_date?->copy()->addDays((int) $account->purchaseOrder->invoice_credit_days);

            if ($dueDate !== null && ($oldestDueDate === null || $dueDate->lt($oldestDueDate))) {
                $oldestDueDate = $dueDate;
            }

            $details[] = [
                'id' => $account->id,
                'number' => $account->acp_purchase_number,
                'date' => $account->purchaseOrder?->invoice_date?->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'days_remaining' => $daysRemaining,
                'total' => round((float) $account->acp_total, 4),
                'paid' => round((float) $account->acp_total - $balance, 4),
                'balance' => $balance,
            ];
        }

        return [
            'supplier' => [
                'id' => $supplier?->id,
                'supplier_name' => $supplier?->supplier_name ?? 'Desconocido',
            ],
            'coin' => $coin,
            'documents_count' => $group->count(),
            'total_balance' => round(array_sum($aging), 4),
            'total_paid' => round(array_sum(array_column($details, 'paid')), 4),
            'aging' => $aging,
            'oldest_due_date' => $oldestDueDate?->toDateString(),
            'details' => $details,
        ];
    }

    private function agingBucket(int $daysRemaining): string
    {
        if ($daysRemaining >= 0) {
            return 'current';
        }

        if ($daysRemaining >= -30) {
            return 'days_1_30';
        }

        if ($daysRemaining >= -60) {
            return 'days_31_60';
        }

        if ($daysRemaining >= -90) {
            return 'days_61_90';
        }

        return 'days_over_90';
    }
}
