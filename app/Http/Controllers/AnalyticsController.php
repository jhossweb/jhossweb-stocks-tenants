<?php

namespace App\Http\Controllers;

use App\Repositories\Dashboard\CashFlowKpiRepository;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Repositories\Dashboard\PayableKpiRepository;
use App\Repositories\Dashboard\PurchaseKpiRepository;
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
        protected PurchaseKpiRepository $purchaseKpiRepo,
    ) {}

    public function show(string $type, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $pageMap = [
            'expenses' => 'Analytics/Expenses',
            'expenses-fijo' => 'Analytics/Expenses',
            'expenses-variable' => 'Analytics/Expenses',
            'cash-flow' => 'Analytics/CashFlow',
            'receivables' => 'Analytics/Receivables',
            'payables' => 'Analytics/Payables',
            'purchases-total' => 'Analytics/Purchases/Total',
            'purchases-supplier' => 'Analytics/Purchases/Supplier',
            'purchases-month' => 'Analytics/Purchases/Month',
            'purchases-status' => 'Analytics/Purchases/Status',
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
            'analyticsType' => $type,
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
            'expenses-fijo' => [
                'total' => $this->expenseKpiRepo->totalFijoByCurrency($month),
                'pending' => $this->expenseKpiRepo->pendingBalance(),
                'trend' => $this->expenseKpiRepo->trendFijo($month),
            ],
            'expenses-variable' => [
                'total' => $this->expenseKpiRepo->totalVariableByCurrency($month),
                'pending' => $this->expenseKpiRepo->pendingBalance(),
                'trend' => $this->expenseKpiRepo->trendVariable($month),
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
            'purchases-total' => [
                'total' => $this->purchaseKpiRepo->totalByCurrency($month),
                'trend' => $this->purchaseKpiRepo->trend($month),
            ],
            'purchases-supplier' => [
                'total' => $this->purchaseKpiRepo->totalByCurrency($month),
                'trend' => $this->purchaseKpiRepo->trend($month),
                'supplierCount' => $this->purchaseKpiRepo->countSuppliers($month),
            ],
            'purchases-month' => [
                'total' => $this->purchaseKpiRepo->totalByCurrency($month),
                'trend' => $this->purchaseKpiRepo->trend($month),
            ],
            'purchases-status' => [
                'total' => $this->purchaseKpiRepo->totalByCurrency($month),
                'trend' => $this->purchaseKpiRepo->trend($month),
                'pending' => $this->purchaseKpiRepo->pendingByCurrency($month),
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
            'expenses-fijo' => [
                'byCategory' => $this->expenseKpiRepo->byCategoryType('fijo', $month),
            ],
            'expenses-variable' => [
                'byCategory' => $this->expenseKpiRepo->byCategoryType('variable', $month),
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
            'purchases-total' => [
                'purchases' => $this->purchaseKpiRepo->allPurchases($month),
            ],
            'purchases-supplier' => [
                'bySupplier' => $this->purchaseKpiRepo->bySupplier($month),
            ],
            'purchases-month' => [
                'byMonth' => $this->purchaseKpiRepo->byMonth(),
            ],
            'purchases-status' => [
                'byStatus' => $this->purchaseKpiRepo->byStatus($month),
            ],
            default => [],
        };
    }
}
