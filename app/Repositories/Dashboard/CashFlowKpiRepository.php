<?php

namespace App\Repositories\Dashboard;

use App\Enum\TypeCoin;
use App\Models\BankTransaction;
use Carbon\CarbonImmutable;

class CashFlowKpiRepository
{
    public function inflowByCurrency(string $month): array
    {
        return $this->sumByTypeAndCoin('inflow', $month);
    }

    public function outflowByCurrency(string $month): array
    {
        return $this->sumByTypeAndCoin('outflow', $month);
    }

    public function netByCurrency(string $month): array
    {
        $inflow = $this->inflowByCurrency($month);
        $outflow = $this->outflowByCurrency($month);

        $currencies = array_unique([...array_keys($inflow), ...array_keys($outflow)]);
        $net = [];

        foreach ($currencies as $currency) {
            $net[$currency] = ($inflow[$currency] ?? 0) - ($outflow[$currency] ?? 0);
        }

        return $net;
    }

    /**
     * Tendencia del neto comparada contra el mes anterior, por moneda.
     *
     * @return array<string, array{direction: string, percentage: float}>
     */
    public function trend(string $month): array
    {
        $previousMonth = CarbonImmutable::createFromFormat('Y-m', $month)
            ->startOfMonth()
            ->subMonth()
            ->format('Y-m');

        $current = $this->netByCurrency($month);
        $previous = $this->netByCurrency($previousMonth);

        $currencies = array_unique([...array_keys($current), ...array_keys($previous)]);

        $trend = [];

        foreach ($currencies as $currency) {
            $currentNet = $current[$currency] ?? 0.0;
            $previousNet = $previous[$currency] ?? 0.0;

            if ($previousNet == 0.0) {
                $trend[$currency] = ['direction' => 'flat', 'percentage' => 0.0];

                continue;
            }

            $percentage = round((($currentNet - $previousNet) / abs($previousNet)) * 100, 1);

            $trend[$currency] = [
                'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat'),
                'percentage' => abs($percentage),
            ];
        }

        return $trend;
    }

    /**
     * Neto por mes para una ventana de meses hacia atrás desde $endMonth.
     *
     * @return array<string, array<string, float>>
     */
    public function monthlyNet(int $months = 6, ?string $endMonth = null): array
    {
        $endMonth ??= now()->format('Y-m');
        $end = CarbonImmutable::createFromFormat('Y-m', $endMonth)->startOfMonth();
        $start = $end->subMonths($months - 1);

        $transactions = BankTransaction::query()
            ->whereBetween('btr_transaction_date', [$start->toDateString(), $end->endOfMonth()->toDateString()])
            ->get(['btr_transaction_date', 'btr_type', 'btr_coin', 'btr_amount']);

        $series = [];

        foreach ($transactions as $transaction) {
            $key = $transaction->btr_transaction_date->format('Y-m');
            $coin = $transaction->btr_coin instanceof TypeCoin ? $transaction->btr_coin->value : (string) $transaction->btr_coin;
            $sign = $transaction->btr_type === 'inflow' ? 1 : -1;
            $series[$key][$coin] = ($series[$key][$coin] ?? 0) + ((float) $transaction->btr_amount * $sign);
        }

        ksort($series);

        return array_map(fn (array $coins) => array_map(fn (float $value) => round($value, 4), $coins), $series);
    }

    public function byAccount(string $type, ?string $month): array
    {
        [$from, $to] = $this->monthRange($month ??= now()->format('Y-m'));

        return BankTransaction::query()
            ->selectRaw('ba.bank_name, ba.bank_currency, bt.btr_coin, SUM(bt.btr_amount) as total')
            ->from('bank_transactions as bt')
            ->join('bank_accounts as ba', 'bt.btr_bank_account_id', '=', 'ba.id')
            ->where('bt.btr_type', $type)
            ->whereBetween('bt.btr_transaction_date', [$from, $to])
            ->groupBy('ba.id', 'ba.bank_name', 'ba.bank_currency', 'bt.btr_coin')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * Neto por cuenta bancaria en una sola query agregada.
     *
     * @return array<int, array{bank_name: string, bank_currency: string, total: float}>
     */
    public function netByAccount(?string $month): array
    {
        [$from, $to] = $this->monthRange($month ??= now()->format('Y-m'));

        return BankTransaction::query()
            ->selectRaw(
                "ba.bank_name, ba.bank_currency, SUM(CASE WHEN bt.btr_type = 'inflow' THEN bt.btr_amount ELSE -bt.btr_amount END) as total",
            )
            ->from('bank_transactions as bt')
            ->join('bank_accounts as ba', 'bt.btr_bank_account_id', '=', 'ba.id')
            ->whereBetween('bt.btr_transaction_date', [$from, $to])
            ->groupBy('ba.id', 'ba.bank_name', 'ba.bank_currency')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'bank_name' => $row->bank_name,
                'bank_currency' => $row->bank_currency,
                'total' => round((float) $row->total, 4),
            ])
            ->toArray();
    }

    /**
     * @return array{0: string, 1: string} [from, to]
     */
    private function monthRange(string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m', $month)->startOfMonth();

        return [$start->toDateString(), $start->endOfMonth()->toDateString()];
    }

    private function sumByTypeAndCoin(string $type, string $month): array
    {
        [$from, $to] = $this->monthRange($month);

        return BankTransaction::query()
            ->selectRaw('btr_coin, SUM(btr_amount) as total')
            ->where('btr_type', $type)
            ->whereBetween('btr_transaction_date', [$from, $to])
            ->groupBy('btr_coin')
            ->pluck('total', 'btr_coin')
            ->toArray();
    }
}
