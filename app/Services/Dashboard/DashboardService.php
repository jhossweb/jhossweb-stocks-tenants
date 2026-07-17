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

        $cache = new DashboardCache;
        $cache->prefetch($month);

        $read = fn (string $key, callable $fallback) => $cache->has($key)
            ? $cache->get($key)
            : $fallback();

        $expensesTotal = $read(
            "dashboard:expenses:total:{$month}",
            fn () => $this->expenseKpiRepo->totalByCurrency($month),
        );

        $receivablesBalance = $read(
            'dashboard:receivables:balance',
            fn () => $this->receivableKpiRepo->balanceByCurrency(),
        );

        $payablesBalance = $read(
            'dashboard:payables:balance',
            fn () => $this->payableKpiRepo->balanceByCurrency(),
        );

        return [
            'expenses' => [
                'total' => $expensesTotal,
                'pending' => $read('dashboard:expenses:pending', fn () => $this->expenseKpiRepo->pendingBalance()),
                'trend' => $this->expenseKpiRepo->trend($month),
            ],
            'cashFlow' => [
                'inflow' => $read("dashboard:cashflow:inflow:{$month}", fn () => $this->cashFlowKpiRepo->inflowByCurrency($month)),
                'outflow' => $read("dashboard:cashflow:outflow:{$month}", fn () => $this->cashFlowKpiRepo->outflowByCurrency($month)),
                'net' => $this->cashFlowKpiRepo->netByCurrency($month),
                'trend' => $this->cashFlowKpiRepo->trend($month),
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
                'topSelling' => $this->productKpiRepo->topSelling(5, $month),
                'worstSelling' => $this->productKpiRepo->worstSelling(5, $month),
            ],
        ];
    }
}
