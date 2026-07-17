<?php

namespace App\Repositories\Dashboard;

use App\Models\AccountsReceivable;

class ReceivableKpiRepository
{
    public function balanceByCurrency(): array
    {
        return AccountsReceivable::query()
            ->selectRaw('so.sls_coin, SUM(ar.acr_balance) as balance, SUM(ar.acr_balance_usd) as balance_usd')
            ->from('accounts_receivable as ar')
            ->join('sale_orders as so', 'ar.acr_sls_number', '=', 'so.sls_number')
            ->whereIn('ar.acr_status', ['pending', 'partial'])
            ->groupBy('so.sls_coin')
            ->get()
            ->keyBy('sls_coin')
            ->map(fn ($row) => [
                'balance' => (float) $row->balance,
                'balance_usd' => (float) ($row->balance_usd ?? 0),
            ])
            ->toArray();
    }

    public function collectedByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return AccountsReceivable::query()
            ->selectRaw('so.sls_coin, SUM(bt.btr_amount) as total')
            ->from('accounts_receivable as ar')
            ->join('sale_orders as so', 'ar.acr_sls_number', '=', 'so.sls_number')
            ->join('bank_transactions as bt', function ($join) {
                $join->on('bt.btr_transactionable_id', '=', 'ar.id')
                    ->where('bt.btr_transactionable_type', '=', 'App\Models\AccountsReceivable');
            })
            ->whereRaw("strftime('%Y-%m', bt.btr_transaction_date) = ?", [$month])
            ->groupBy('so.sls_coin')
            ->pluck('total', 'sls_coin')
            ->toArray();
    }

    public function byCustomer(): array
    {
        return AccountsReceivable::query()
            ->selectRaw('c.customer_name, so.sls_coin, SUM(ar.acr_balance) as balance, SUM(ar.acr_balance_usd) as balance_usd')
            ->from('accounts_receivable as ar')
            ->join('sale_orders as so', 'ar.acr_sls_number', '=', 'so.sls_number')
            ->join('customers as c', 'so.sls_customer_id', '=', 'c.id')
            ->whereIn('ar.acr_status', ['pending', 'partial'])
            ->groupBy('c.id', 'so.sls_coin')
            ->orderByDesc('balance')
            ->get()
            ->toArray();
    }
}
