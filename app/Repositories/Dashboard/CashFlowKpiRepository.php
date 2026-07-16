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
}
