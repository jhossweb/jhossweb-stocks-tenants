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

        $expensesTotal = $this->expenseKpiRepo->totalByCurrency($month);
        $receivablesBalance = $this->receivableKpiRepo->balanceByCurrency();

        return [
            'expenses' => [
                'total' => $expensesTotal,
                'pending' => $this->expenseKpiRepo->pendingBalance(),
                'trend' => $this->expenseKpiRepo->trend($month),
            ],
            'cashFlow' => [
                'inflow' => $this->cashFlowKpiRepo->inflowByCurrency($month),
                'outflow' => $this->cashFlowKpiRepo->outflowByCurrency($month),
                'net' => $this->cashFlowKpiRepo->netByCurrency($month),
                'trend' => $this->cashFlowKpiRepo->trend($month),
            ],
            'receivables' => [
                'balance' => collect($receivablesBalance)->map(fn ($item) => $item['balance'])->toArray(),
                'balance_usd' => collect($receivablesBalance)->map(fn ($item) => $item['balance_usd'])->toArray(),
                'collected' => $this->receivableKpiRepo->collectedByCurrency($month),
            ],
            'payables' => [
                'balance' => collect($payablesBalance = $this->payableKpiRepo->balanceByCurrency())->map(fn ($item) => $item['balance'])->toArray(),
                'balance_usd' => collect($payablesBalance)->map(fn ($item) => $item['balance_usd'])->toArray(),
                'paid' => $this->payableKpiRepo->paidByCurrency($month),
            ],
            'products' => [
                'totalSkus' => $this->productKpiRepo->totalSkus(),
                'lowStock' => $this->productKpiRepo->lowStock(),
                'topSelling' => $this->productKpiRepo->topSelling(5, $month),
                'worstSelling' => $this->productKpiRepo->worstSelling(5, $month),
            ],
        ];
    }
}
