<?php

namespace App\Services\Dashboard;

use App\DTOs\Dashboard\UsdSummary;
use App\Repositories\Dashboard\CashFlowKpiRepository;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Repositories\Dashboard\PayableKpiRepository;
use App\Repositories\Dashboard\PurchaseKpiRepository;
use App\Repositories\Dashboard\ReceivableKpiRepository;
use App\Repositories\Dashboard\SalesKpiRepository;
use App\Repositories\Inventory\InventoryKpiRepository;
use App\Services\Finance\RateService;
use Illuminate\Support\Arr;

class DashboardService
{
    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
        protected CashFlowKpiRepository $cashFlowKpiRepo,
        protected ReceivableKpiRepository $receivableKpiRepo,
        protected PayableKpiRepository $payableKpiRepo,
        protected PurchaseKpiRepository $purchaseKpiRepo,
        protected SalesKpiRepository $salesKpiRepo,
        protected InventoryKpiRepository $inventoryKpiRepo,
        protected ProfitLossService $profitLossService,
        protected RateService $rateService,
    ) {}

    public function getKpis(?string $month): array
    {
        $month ??= now()->format('Y-m');
        $prevMonth = $this->previousMonth($month);

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

        $expensesFijoUsd = $this->toUsdSummary($expensesFijo);
        $expensesVariableUsd = $this->toUsdSummary($expensesVariable);
        $purchasesUsd = $this->toUsdSummary($purchasesTotal);

        return [
            'profitLoss' => $this->withUsd($profitLoss),
            'expensesFijo' => [
                'total' => $expensesFijo,
                'usd' => $expensesFijoUsd->usd,
                'rate_date' => $expensesFijoUsd->rateDate,
                'trend' => $this->computeTrend($expensesFijo, $expensesFijoPrev),
            ],
            'expensesVariable' => [
                'total' => $expensesVariable,
                'usd' => $expensesVariableUsd->usd,
                'rate_date' => $expensesVariableUsd->rateDate,
                'trend' => $this->computeTrend($expensesVariable, $expensesVariablePrev),
            ],
            'cashFlow' => [
                'inflow' => $cashFlowInflow,
                'outflow' => $cashFlowOutflow,
                'net' => $cashFlowNet,
                'usd' => [
                    'inflow' => $this->toUsdSummary($cashFlowInflow)->usd,
                    'outflow' => $this->toUsdSummary($cashFlowOutflow)->usd,
                    'net' => $this->toUsdSummary($cashFlowNet)->usd,
                ],
                'trend' => $this->computeTrendByCurrency($cashFlowNet, $prevNet),
            ],
            'receivables' => [
                'balance' => Arr::pluck($receivablesBalance, 'balance'),
                'balance_usd' => Arr::pluck($receivablesBalance, 'balance_usd'),
                'collected' => $read("dashboard:receivables:collected:{$month}", fn () => $this->receivableKpiRepo->collectedByCurrency($month)),
            ],
            'payables' => [
                'balance' => Arr::pluck($payablesBalance, 'balance'),
                'balance_usd' => Arr::pluck($payablesBalance, 'balance_usd'),
                'paid' => $read("dashboard:payables:paid:{$month}", fn () => $this->payableKpiRepo->paidByCurrency($month)),
            ],
            'purchases' => [
                'total' => $purchasesTotal,
                'usd' => $purchasesUsd->usd,
                'rate_date' => $purchasesUsd->rateDate,
                'trend' => $this->computeTrend($purchasesTotal, $purchasesPrevTotal),
                'supplierCount' => $purchasesSupplierCount,
                'pending' => $purchasesPending,
            ],
        ];
    }

    public function getCategoryAggregates(?string $month): array
    {
        $month ??= now()->format('Y-m');
        $prevMonth = $this->previousMonth($month);

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
            'ventas' => $this->aggregate($ventasHoy),
            'gastos' => [
                ...$this->aggregate($gastosTotal),
                'trend' => $this->computeTrend($gastosTotal, $gastosPrevTotal),
            ],
            'finanzas' => $this->aggregate(Arr::pluck($receivablesBalance, 'balance')),
            'compras' => [
                ...$this->aggregate($purchasesTotal),
                'trend' => $this->computeTrend($purchasesTotal, $purchasesPrevTotal),
            ],
            'inventario' => $this->aggregate($this->inventoryKpiRepo->inventoryValue()),
        ];
    }

    /**
     * Desglose de gastos por categoría del período, con participación porcentual.
     *
     * Una fila por categoría con su desglose por moneda anidado, para que el
     * frontend pueda ordenar y comparar sin tener que reagrupar.
     *
     * @return array{usd: float|null, rate_date: string|null, has_rate: bool,
     *               total_by_currency: array<string, float>,
     *               categories: array<int, array{category_id: int, exc_name: string,
     *               exc_type: string, amounts: array<string, float>,
     *               usd: float|null, share_pct: float}>}
     */
    public function getExpenseBreakdown(?string $month = null): array
    {
        $month ??= now()->format('Y-m');

        $cache = new DashboardCache;
        $cache->prefetch($month);

        $key = "dashboard:expenses:by_category:{$month}";

        if (! $cache->has($key)) {
            $cache->set($key, $this->expenseKpiRepo->totalByCategory($month));
        }

        $rows = $cache->get($key, []);

        $summary = $this->toUsdSummary($this->mergeAmounts(Arr::pluck($rows, 'amounts')));

        $categories = array_map(function (array $row) {
            $usd = $this->toUsdSummary($row['amounts'])->usd;

            $row['usd'] = $usd;

            return $row;
        }, $rows);

        // El denominador del % tiene que ser la misma magnitud que el
        // numerador. Sin tasa no existe equivalente USD: se ordena por el
        // monto nativo y el % queda en 0 en vez de repartir un total sin sentido.
        $denominator = $summary->usd !== null ? $summary->usd : 0.0;

        foreach ($categories as $index => $category) {
            $categories[$index]['share_pct'] = $denominator > 0
                ? round(($category['usd'] / $denominator) * 100, 1)
                : 0.0;
        }

        usort($categories, fn (array $a, array $b) => [
            $b['usd'] ?? 0,
            $this->rawTotal($b['amounts']),
        ] <=> [
            $a['usd'] ?? 0,
            $this->rawTotal($a['amounts']),
        ]);

        return [
            ...$summary->toArray(),
            'total_by_currency' => $summary->byCurrency,
            'categories' => $categories,
        ];
    }

    /**
     * Envuelve cada bloque `{total, trend}` de un KPI con su equivalente USD.
     *
     * @param  array<string, array{total: array<string, float>, trend: array<string, mixed>}>  $profitLoss
     * @return array<string, array{total: array<string, float>, trend: array<string, mixed>, usd: float|null, rate_date: string|null}>
     */
    private function withUsd(array $profitLoss): array
    {
        foreach ($profitLoss as $key => $item) {
            $summary = $this->toUsdSummary($item['total']);

            $profitLoss[$key]['usd'] = $summary->usd;
            $profitLoss[$key]['rate_date'] = $summary->rateDate;
        }

        return $profitLoss;
    }

    /**
     * @param  array<string, float>  $byCurrency
     * @return array{total: array<string, float>, usd: float|null, rate_date: string|null, has_rate: bool}
     */
    private function aggregate(array $byCurrency): array
    {
        $summary = $this->toUsdSummary($byCurrency);

        return [
            'total' => $summary->byCurrency,
            'usd' => $summary->usd,
            'rate_date' => $summary->rateDate,
            'has_rate' => $summary->hasRate(),
        ];
    }

    /**
     * @param  array<string, mixed>  $byCurrency
     */
    private function toUsdSummary(array $byCurrency): UsdSummary
    {
        return UsdSummary::from($byCurrency, $this->rateService->getLatestRate());
    }

    /**
     * @param  array<int, array<string, float>>  $sets
     * @return array<string, float>
     */
    private function mergeAmounts(array $sets): array
    {
        $merged = [];

        foreach ($sets as $set) {
            foreach ($set as $coin => $amount) {
                $merged[$coin] = ($merged[$coin] ?? 0) + (float) $amount;
            }
        }

        return $merged;
    }

    /**
     * Suma de montos sin conversión. Solo sirve como desempate/ordenamiento
     * cuando no hay tasa: sumar monedas distintas no es un total contable.
     *
     * @param  array<string, float>  $byCurrency
     */
    private function rawTotal(array $byCurrency): float
    {
        return (float) array_sum(array_map('floatval', $byCurrency));
    }

    private function previousMonth(string $month): string
    {
        return date('Y-m', strtotime("{$month}-01 -1 month"));
    }

    /**
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     * @return array<string, float>
     */
    private function sumByCurrency(array $a, array $b): array
    {
        $currencies = array_unique([...array_keys($a), ...array_keys($b)]);
        $result = [];

        foreach ($currencies as $currency) {
            $result[$currency] = ($a[$currency] ?? 0) + ($b[$currency] ?? 0);
        }

        return $result;
    }

    /**
     * @param  array<string, float>  $inflow
     * @param  array<string, float>  $outflow
     * @return array<string, float>
     */
    private function computeNetByCurrency(array $inflow, array $outflow): array
    {
        $currencies = array_unique([...array_keys($inflow), ...array_keys($outflow)]);
        $net = [];

        foreach ($currencies as $currency) {
            $net[$currency] = ($inflow[$currency] ?? 0) - ($outflow[$currency] ?? 0);
        }

        return $net;
    }

    /**
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array<string, array{direction: string, percentage: float}>
     */
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

    /**
     * Tendencia de un total multi-moneda, comparable en USD.
     *
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array{direction: string, percentage: float, reliable: bool}
     */
    private function computeTrend(array $current, array $previous): array
    {
        return TrendCalculator::compute($current, $previous, $this->rateService->getLatestRate());
    }
}
