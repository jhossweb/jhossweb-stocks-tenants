<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\CashFlowKpiRepository;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Repositories\Dashboard\PayableKpiRepository;
use App\Repositories\Dashboard\ProductKpiRepository;
use App\Repositories\Dashboard\ReceivableKpiRepository;

class DashboardService
{
    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
        protected CashFlowKpiRepository $cashFlowKpiRepo,
        protected ReceivableKpiRepository $receivableKpiRepo,
        protected PayableKpiRepository $payableKpiRepo,
        protected ProductKpiRepository $productKpiRepo,
    ) {}

    public function getKpis(?string $month): array
    {
        $month ??= now()->format('Y-m');
        $prevMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        $cache = new DashboardCache;
        $cache->prefetch($month);

        $read = function (string $key, callable $fallback) use ($cache) {
            if ($cache->has($key)) {
                return $cache->get($key);
            }

            $value = $fallback();
            $cache->set($key, $value);

            return $value;
        };

        // --- Expenses ---
        $expensesTotal = $read(
            "dashboard:expenses:total:{$month}",
            fn () => $this->expenseKpiRepo->totalByCurrency($month),
        );

        $expensesPrevTotal = $read(
            "dashboard:expenses:total:{$prevMonth}",
            fn () => $this->expenseKpiRepo->totalByCurrency($prevMonth),
        );

        // --- Cash Flow ---
        $cashFlowInflow = $read(
            "dashboard:cashflow:inflow:{$month}",
            fn () => $this->cashFlowKpiRepo->inflowByCurrency($month),
        );

        $cashFlowOutflow = $read(
            "dashboard:cashflow:outflow:{$month}",
            fn () => $this->cashFlowKpiRepo->outflowByCurrency($month),
        );

        $prevInflow = $read(
            "dashboard:cashflow:inflow:{$prevMonth}",
            fn () => $this->cashFlowKpiRepo->inflowByCurrency($prevMonth),
        );

        $prevOutflow = $read(
            "dashboard:cashflow:outflow:{$prevMonth}",
            fn () => $this->cashFlowKpiRepo->outflowByCurrency($prevMonth),
        );

        // --- Receivables ---
        $receivablesBalance = $read(
            'dashboard:receivables:balance',
            fn () => $this->receivableKpiRepo->balanceByCurrency(),
        );

        // --- Payables ---
        $payablesBalance = $read(
            'dashboard:payables:balance',
            fn () => $this->payableKpiRepo->balanceByCurrency(),
        );

        // --- Products ---
        $productsTop = $read(
            "dashboard:products:top:{$month}:5",
            fn () => $this->productKpiRepo->topSelling(5, $month),
        );

        $productsWorst = $read(
            "dashboard:products:worst:{$month}:5",
            fn () => $this->productKpiRepo->worstSelling(5, $month),
        );

        // --- Compute aggregates locally (no extra queries) ---
        $cashFlowNet = $this->computeNetByCurrency($cashFlowInflow, $cashFlowOutflow);
        $prevNet = $this->computeNetByCurrency($prevInflow, $prevOutflow);

        return [
            'expenses' => [
                'total' => $expensesTotal,
                'pending' => $read('dashboard:expenses:pending', fn () => $this->expenseKpiRepo->pendingBalance()),
                'trend' => $this->computeTrend($expensesTotal, $expensesPrevTotal),
            ],
            'cashFlow' => [
                'inflow' => $cashFlowInflow,
                'outflow' => $cashFlowOutflow,
                'net' => $cashFlowNet,
                'trend' => $this->computeTrend($cashFlowNet, $prevNet),
            ],
            'receivables' => [
                'balance' => collect($receivablesBalance)->map(fn ($item) => $item['balance'])->toArray(),
                'balance_usd' => collect($receivablesBalance)->map(fn ($item) => $item['balance_usd'])->toArray(),
                'collected' => $read("dashboard:receivables:collected:{$month}", fn () => $this->receivableKpiRepo->collectedByCurrency($month)),
            ],
            'payables' => [
                'balance' => collect($payablesBalance)->map(fn ($item) => $item['balance'])->toArray(),
                'balance_usd' => collect($payablesBalance)->map(fn ($item) => $item['balance_usd'])->toArray(),
                'paid' => $read("dashboard:payables:paid:{$month}", fn () => $this->payableKpiRepo->paidByCurrency($month)),
            ],
            'products' => [
                'totalSkus' => $read('dashboard:products:total_skus', fn () => $this->productKpiRepo->totalSkus()),
                'lowStock' => $read('dashboard:products:low_stock', fn () => $this->productKpiRepo->lowStock()),
                'topSelling' => $productsTop,
                'worstSelling' => $productsWorst,
            ],
        ];
    }

    private function computeNetByCurrency(array $inflow, array $outflow): array
    {
        $currencies = array_unique([...array_keys($inflow), ...array_keys($outflow)]);
        $net = [];

        foreach ($currencies as $currency) {
            $net[$currency] = ($inflow[$currency] ?? 0) - ($outflow[$currency] ?? 0);
        }

        return $net;
    }

    private function computeTrend(array $current, array $previous): array
    {
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
