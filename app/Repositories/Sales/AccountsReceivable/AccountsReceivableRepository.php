<?php

namespace App\Repositories\Sales\AccountsReceivable;

use App\Models\AccountsReceivable;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountsReceivableRepository
{
    public function creditPending(?string $search = null, ?string $due = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->baseCreditQuery();

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('accounts_receivable.acr_sls_number', 'like', "%{$search}%")
                    ->orWhereHas('saleOrder.customer', function (Builder $sq) use ($search) {
                        $sq->where('clt_name', 'like', "%{$search}%")
                            ->orWhere('clt_code', 'like', "%{$search}%")
                            ->orWhere('clt_cedula', 'like', "%{$search}%");
                    });
            });
        }

        if ($due === 'overdue') {
            $query->whereDate('sale_orders.sls_due_date', '<', today());
        } elseif ($due === 'upcoming') {
            $query->whereDate('sale_orders.sls_due_date', '>=', today());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getClientCreditPending(Customer $customer): Collection
    {
        return $this->baseCreditQuery()
            ->where('sale_orders.sls_customer_id', $customer->id)
            ->get();
    }

    /**
     * Resumen consolidado de CxC agrupado por cliente y moneda, con
     * antigüedad de saldos y el detalle de los documentos pendientes.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function summaryByCustomer(?string $search = null): \Illuminate\Support\Collection
    {
        $accounts = $this->baseCreditQuery(withPayments: false)->get();

        $accounts->each(fn ($account) => $account->saleOrder?->append('days_remaining'));

        if ($search !== null && $search !== '') {
            $needle = mb_strtolower($search);
            $accounts = $accounts->filter(
                fn ($account) => $this->matchesCustomerSearch($account, $needle)
            );
        }

        return $accounts
            ->groupBy(fn ($account) => ($account->saleOrder?->customer?->id ?? 0).'|'.($account->saleOrder?->sls_coin ?? 'USD'))
            ->map(fn (Collection $group) => $this->buildCustomerSummary($group))
            ->sortBy('customer.clt_name')
            ->values();
    }

    private function matchesCustomerSearch($account, string $needle): bool
    {
        $customer = $account->saleOrder?->customer;

        return str_contains(mb_strtolower((string) $account->acr_sls_number), $needle)
            || str_contains(mb_strtolower((string) $customer?->clt_name), $needle)
            || str_contains(mb_strtolower((string) $customer?->clt_code), $needle)
            || str_contains(mb_strtolower((string) $customer?->clt_cedula), $needle);
    }

    /**
     * @param  Collection<int, AccountsReceivable>  $group
     * @return array<string, mixed>
     */
    private function buildCustomerSummary(Collection $group): array
    {
        $first = $group->first();
        $customer = $first->saleOrder?->customer;
        $coin = $first->saleOrder?->sls_coin ?? 'USD';

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
            $balance = round((float) $account->acr_balance, 4);
            $daysRemaining = (int) ($account->saleOrder?->days_remaining ?? 0);

            $aging[$this->agingBucket($daysRemaining)] += $balance;

            $dueDate = $account->saleOrder?->sls_due_date
                ?? $account->saleOrder?->sls_date?->copy()->addDays((int) $account->saleOrder->sls_credit_days);

            if ($dueDate !== null && ($oldestDueDate === null || $dueDate->lt($oldestDueDate))) {
                $oldestDueDate = $dueDate;
            }

            $details[] = [
                'id' => $account->id,
                'number' => $account->acr_sls_number,
                'date' => $account->saleOrder?->sls_date?->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'days_remaining' => $daysRemaining,
                'total' => round((float) $account->acr_total, 4),
                'paid' => round((float) $account->acr_total - $balance, 4),
                'balance' => $balance,
            ];
        }

        return [
            'customer' => [
                'id' => $customer?->id,
                'clt_code' => $customer?->clt_code,
                'clt_name' => $customer?->clt_name ?? 'Desconocido',
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

    private function baseCreditQuery(bool $withPayments = true): Builder
    {
        $with = ['saleOrder.customer', 'saleOrder.transactionType'];

        if ($withPayments) {
            $with[] = 'payments.bankTransaction';
        }

        return AccountsReceivable::with($with)
            ->join('sale_orders', 'sale_orders.sls_number', '=', 'accounts_receivable.acr_sls_number')
            ->select('accounts_receivable.*')
            ->whereIn('accounts_receivable.acr_status', ['pending', 'partial'])
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->orderBy('sale_orders.sls_due_date');
    }
}
