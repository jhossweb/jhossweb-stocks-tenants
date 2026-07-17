<?php

namespace App\Http\Controllers;

use App\Repositories\Dashboard\CashFlowKpiRepository;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Repositories\Dashboard\PayableKpiRepository;
use App\Repositories\Dashboard\ProductKpiRepository;
use App\Repositories\Dashboard\ReceivableKpiRepository;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
        protected CashFlowKpiRepository $cashFlowKpiRepo,
        protected ReceivableKpiRepository $receivableKpiRepo,
        protected PayableKpiRepository $payableKpiRepo,
        protected ProductKpiRepository $productKpiRepo,
    ) {}

    public function show(string $type, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $pageMap = [
            'expenses' => 'Analytics/Expenses',
            'cash-flow' => 'Analytics/CashFlow',
            'receivables' => 'Analytics/Receivables',
            'payables' => 'Analytics/Payables',
            'products' => 'Analytics/Products',
        ];

        if (! isset($pageMap[$type])) {
            abort(404);
        }

        $summary = $this->getSummary($type, $month);
        $deferred = fn () => $this->getDetails($type, $month);

        return Inertia::render($pageMap[$type], [
            'summary' => $summary,
            'details' => Inertia::defer($deferred),
            'selectedMonth' => $month,
        ]);
    }

    private function getSummary(string $type, string $month): array
    {
        return match ($type) {
            'expenses' => [
                'total' => $this->expenseKpiRepo->totalByCurrency($month),
                'pending' => $this->expenseKpiRepo->pendingBalance(),
                'trend' => $this->expenseKpiRepo->trend($month),
            ],
            'cash-flow' => [
                'inflow' => $this->cashFlowKpiRepo->inflowByCurrency($month),
                'outflow' => $this->cashFlowKpiRepo->outflowByCurrency($month),
                'net' => $this->cashFlowKpiRepo->netByCurrency($month),
                'trend' => $this->cashFlowKpiRepo->trend($month),
            ],
            'receivables' => [
                'balance' => $this->receivableKpiRepo->balanceByCurrency(),
                'collected' => $this->receivableKpiRepo->collectedByCurrency($month),
            ],
            'payables' => [
                'balance' => $this->payableKpiRepo->balanceByCurrency(),
                'paid' => $this->payableKpiRepo->paidByCurrency($month),
            ],
            'products' => [
                'totalSkus' => $this->productKpiRepo->totalSkus(),
                'lowStock' => $this->productKpiRepo->lowStock(),
            ],
            default => [],
        };
    }

    private function getDetails(string $type, string $month): array
    {
        return match ($type) {
            'expenses' => [
                'byCategory' => $this->expenseKpiRepo->byCategory($month),
            ],
            'cash-flow' => [
                'inflowByAccount' => $this->cashFlowKpiRepo->byAccount('inflow', $month),
                'outflowByAccount' => $this->cashFlowKpiRepo->byAccount('outflow', $month),
                'netByAccount' => $this->cashFlowKpiRepo->netByAccount($month),
            ],
            'receivables' => [
                'byCustomer' => $this->receivableKpiRepo->byCustomer(),
            ],
            'payables' => [
                'bySupplier' => $this->payableKpiRepo->bySupplier(),
            ],
            'products' => [
                'topSelling' => $this->productKpiRepo->topSellingReport($month, 10),
                'worstSelling' => $this->productKpiRepo->worstSellingReport($month, 10),
                'lowStock' => $this->productKpiRepo->lowStock(),
            ],
            default => [],
        };
    }
}
