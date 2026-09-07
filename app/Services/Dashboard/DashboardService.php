<?php

namespace App\Services\Dashboard;

use App\Repositories\Dashboard\CashFlowKpiRepository;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Repositories\Dashboard\PayableKpiRepository;
use App\Repositories\Dashboard\PurchaseKpiRepository;
use App\Repositories\Dashboard\ReceivableKpiRepository;
use App\Repositories\Dashboard\SalesKpiRepository;

class DashboardService
{
    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
        protected CashFlowKpiRepository $cashFlowKpiRepo,
        protected ReceivableKpiRepository $receivableKpiRepo,
        protected PayableKpiRepository $payableKpiRepo,
        protected PurchaseKpiRepository $purchaseKpiRepo,
        protected SalesKpiRepository $salesKpiRepo,
        protected ProfitLossService $profitLossService,
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

        // --- Expenses (Fijo / Variable) ---
        $expensesFijo = $read(
            "dashboard:expenses:fijo:{$month}",
            fn () => $this->expenseKpiRepo->totalFijoByCurrency($month),
        );

        $expensesFijoPrev = $read(
            "dashboard:expenses:fijo:{$prevMonth}",
            fn () => $this->expenseKpiRepo->totalFijoByCurrency($prevMonth),
        );

        $expensesVariable = $read(
            "dashboard:expenses:variable:{$month}",
            fn () => $this->expenseKpiRepo->totalVariableByCurrency($month),
        );

        $expensesVariablePrev = $read(
            "dashboard:expenses:variable:{$prevMonth}",
            fn () => $this->expenseKpiRepo->totalVariableByCurrency($prevMonth),
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

        // --- Profit & Loss ---
        $profitLoss = $read(
            "dashboard:profitloss:{$month}",
            fn () => $this->profitLossService->getProfitLoss($month),
        );

        // --- Purchases ---
        $purchasesTotal = $read(
            "dashboard:purchases:total:{$month}",
            fn () => $this->purchaseKpiRepo->totalByCurrency($month),
        );

        $purchasesPrevTotal = $read(
            "dashboard:purchases:total:{$prevMonth}",
            fn () => $this->purchaseKpiRepo->totalByCurrency($prevMonth),
        );

        $purchasesPending = $read(
            "dashboard:purchases:pending:{$month}",
            fn () => $this->purchaseKpiRepo->pendingByCurrency($month),
        );

        $purchasesSupplierCount = $read(
            "dashboard:purchases:supplier_count:{$month}",
            fn () => $this->purchaseKpiRepo->countSuppliers($month),
        );

        // --- Compute aggregates locally (no extra queries) ---
        $cashFlowNet = $this->computeNetByCurrency($cashFlowInflow, $cashFlowOutflow);
        $prevNet = $this->computeNetByCurrency($prevInflow, $prevOutflow);

        return [
            'profitLoss' => $profitLoss,
            'expensesFijo' => [
                'total' => $expensesFijo,
                'trend' => $this->computeTrend($expensesFijo, $expensesFijoPrev),
            ],
            'expensesVariable' => [
                'total' => $expensesVariable,
                'trend' => $this->computeTrend($expensesVariable, $expensesVariablePrev),
            ],
            'cashFlow' => [
                'inflow' => $cashFlowInflow,
                'outflow' => $cashFlowOutflow,
                'net' => $cashFlowNet,
                'trend' => $this->computeTrendByCurrency($cashFlowNet, $prevNet),
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
            'purchases' => [
                'total' => $purchasesTotal,
                'trend' => $this->computeTrend($purchasesTotal, $purchasesPrevTotal),
                'supplierCount' => $purchasesSupplierCount,
                'pending' => $purchasesPending,
            ],
        ];
    }

    public function getCategoryAggregates(?string $month): array
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

        // --- Ventas (del día actual) ---
        $ventasHoy = $this->salesKpiRepo->todayByCurrency();

        // --- Gastos (fijo + variable combinados) ---
        $expensesFijo = $read(
            "dashboard:expenses:fijo:{$month}",
            fn () => $this->expenseKpiRepo->totalFijoByCurrency($month),
        );
        $expensesVariable = $read(
            "dashboard:expenses:variable:{$month}",
            fn () => $this->expenseKpiRepo->totalVariableByCurrency($month),
        );
        $expensesFijoPrev = $read(
            "dashboard:expenses:fijo:{$prevMonth}",
            fn () => $this->expenseKpiRepo->totalFijoByCurrency($prevMonth),
        );
        $expensesVariablePrev = $read(
            "dashboard:expenses:variable:{$prevMonth}",
            fn () => $this->expenseKpiRepo->totalVariableByCurrency($prevMonth),
        );

        $gastosTotal = $this->sumByCurrency($expensesFijo, $expensesVariable);
        $gastosPrevTotal = $this->sumByCurrency($expensesFijoPrev, $expensesVariablePrev);

        // --- Finanzas ---
        $receivablesBalance = $read(
            'dashboard:receivables:balance',
            fn () => $this->receivableKpiRepo->balanceByCurrency(),
        );

        // --- Compras ---
        $purchasesTotal = $read(
            "dashboard:purchases:total:{$month}",
            fn () => $this->purchaseKpiRepo->totalByCurrency($month),
        );

        $purchasesPrevTotal = $read(
            "dashboard:purchases:total:{$prevMonth}",
            fn () => $this->purchaseKpiRepo->totalByCurrency($prevMonth),
        );

        return [
            'ventas' => [
                'total' => $ventasHoy,
            ],
            'gastos' => [
                'total' => $gastosTotal,
                'trend' => $this->computeTrend($gastosTotal, $gastosPrevTotal),
            ],
            'finanzas' => [
                'total' => collect($receivablesBalance)->map(fn ($item) => $item['balance'])->toArray(),
            ],
            'compras' => [
                'total' => $purchasesTotal,
                'trend' => $this->computeTrend($purchasesTotal, $purchasesPrevTotal),
            ],
        ];
    }

    private function sumByCurrency(array $a, array $b): array
    {
        $currencies = array_unique([...array_keys($a), ...array_keys($b)]);
        $result = [];

        foreach ($currencies as $currency) {
            $result[$currency] = ($a[$currency] ?? 0) + ($b[$currency] ?? 0);
        }

        return $result;
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

    private function computeTrendByCurrency(array $current, array $previous): array
    {
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
