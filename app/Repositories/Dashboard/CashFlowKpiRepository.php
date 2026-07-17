<?php

namespace App\Repositories\Dashboard;

use App\Models\BankTransaction;
use Illuminate\Support\Facades\Cache;

class CashFlowKpiRepository
{
    public function inflowByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return Cache::remember("dashboard:cashflow:inflow:{$month}", 60, function () use ($month) {
            return BankTransaction::query()
                ->selectRaw('btr_coin, SUM(btr_amount) as total')
                ->where('btr_type', 'inflow')
                ->whereRaw("strftime('%Y-%m', btr_transaction_date) = ?", [$month])
                ->groupBy('btr_coin')
                ->pluck('total', 'btr_coin')
                ->toArray();
        });
    }

    public function outflowByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return Cache::remember("dashboard:cashflow:outflow:{$month}", 60, function () use ($month) {
            return BankTransaction::query()
                ->selectRaw('btr_coin, SUM(btr_amount) as total')
                ->where('btr_type', 'outflow')
                ->whereRaw("strftime('%Y-%m', btr_transaction_date) = ?", [$month])
                ->groupBy('btr_coin')
                ->pluck('total', 'btr_coin')
                ->toArray();
        });
    }

    public function netByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        $inflow = $this->inflowByCurrency($month);
        $outflow = $this->outflowByCurrency($month);

        $currencies = array_unique([...array_keys($inflow), ...array_keys($outflow)]);
        $net = [];

        foreach ($currencies as $currency) {
            $net[$currency] = ($inflow[$currency] ?? 0) - ($outflow[$currency] ?? 0);
        }

        return $net;
    }

    public function trend(?string $month): array
    {
        $month ??= now()->format('Y-m');
        $previousMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        $current = $this->netByCurrency($month);
        $previous = $this->netByCurrency($previousMonth);

        $totalCurrent = array_sum($current);
        $totalPrevious = array_sum($previous);

        if ($totalPrevious <= 0) {
            return ['direction' => 'flat', 'percentage' => 0];
        }

        $percentage = round((($totalCurrent - $totalPrevious) / $totalPrevious) * 100, 1);

        return [
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat'),
            'percentage' => abs($percentage),
        ];
    }

    public function byAccount(string $type, ?string $month): array
    {
        $month ??= now()->format('Y-m');

        return Cache::remember("dashboard:cashflow:{$type}:account:{$month}", 300, function () use ($type, $month) {
            return BankTransaction::query()
                ->selectRaw('ba.bank_name, ba.bank_currency, bt.btr_coin, SUM(bt.btr_amount) as total')
                ->from('bank_transactions as bt')
                ->join('bank_accounts as ba', 'bt.btr_bank_account_id', '=', 'ba.id')
                ->where('bt.btr_type', $type)
                ->whereRaw("strftime('%Y-%m', bt.btr_transaction_date) = ?", [$month])
                ->groupBy('ba.id', 'bt.btr_coin')
                ->orderByDesc('total')
                ->get()
                ->toArray();
        });
    }

    public function netByAccount(?string $month): array
    {
        $month ??= now()->format('Y-m');
        $inflow = $this->byAccount('inflow', $month);
        $outflow = $this->byAccount('outflow', $month);

        $accounts = collect([...$inflow, ...$outflow])->pluck('bank_name')->unique();
        $net = [];

        foreach ($accounts as $account) {
            $in = collect($inflow)->firstWhere('bank_name', $account)['total'] ?? 0;
            $out = collect($outflow)->firstWhere('bank_name', $account)['total'] ?? 0;
            $net[] = [
                'bank_name' => $account,
                'bank_currency' => collect($inflow)->firstWhere('bank_name', $account)['bank_currency']
                    ?? collect($outflow)->firstWhere('bank_name', $account)['bank_currency'] ?? '',
                'total' => $in - $out,
            ];
        }

        return $net;
    }
}
