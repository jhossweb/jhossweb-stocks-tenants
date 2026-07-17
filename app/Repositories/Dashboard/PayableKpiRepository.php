<?php

namespace App\Repositories\Dashboard;

use App\Models\AccountsPayable;

class PayableKpiRepository
{
    public function balanceByCurrency(): array
    {
        return AccountsPayable::query()
            ->selectRaw('po.invoice_coin, SUM(ap.acp_balance) as balance, SUM(ap.acp_balance_usd) as balance_usd')
            ->from('accounts_payables as ap')
            ->join('purchase_orders as po', 'ap.acp_purchase_number', '=', 'po.invoice_number')
            ->whereIn('ap.acp_status', ['pending', 'partial'])
            ->groupBy('po.invoice_coin')
            ->get()
            ->keyBy('invoice_coin')
            ->map(fn ($row) => [
                'balance' => (float) $row->balance,
                'balance_usd' => (float) ($row->balance_usd ?? 0),
            ])
            ->toArray();
    }

    public function paidByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return AccountsPayable::query()
            ->selectRaw('po.invoice_coin, SUM(bt.btr_amount) as total')
            ->from('accounts_payables as ap')
            ->join('purchase_orders as po', 'ap.acp_purchase_number', '=', 'po.invoice_number')
            ->join('bank_transactions as bt', function ($join) {
                $join->on('bt.btr_transactionable_id', '=', 'ap.id')
                    ->where('bt.btr_transactionable_type', '=', 'App\Models\AccountsPayable');
            })
            ->whereRaw("strftime('%Y-%m', bt.btr_transaction_date) = ?", [$month])
            ->groupBy('po.invoice_coin')
            ->pluck('total', 'invoice_coin')
            ->toArray();
    }

    public function bySupplier(): array
    {
        return AccountsPayable::query()
            ->selectRaw('s.supplier_name, po.invoice_coin, SUM(ap.acp_balance) as balance, SUM(ap.acp_balance_usd) as balance_usd')
            ->from('accounts_payables as ap')
            ->join('purchase_orders as po', 'ap.acp_purchase_number', '=', 'po.invoice_number')
            ->join('suppliers as s', 'po.invoice_supplier_id', '=', 's.id')
            ->whereIn('ap.acp_status', ['pending', 'partial'])
            ->groupBy('s.id', 'po.invoice_coin')
            ->orderByDesc('balance')
            ->get()
            ->toArray();
    }
}
