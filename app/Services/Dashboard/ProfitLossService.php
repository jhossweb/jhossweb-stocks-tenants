<?php

namespace App\Services\Dashboard;

use App\Models\SaleOrder;
use App\Repositories\Dashboard\ExpenseKpiRepository;

class ProfitLossService
{
    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
    ) {}

    public function getProfitLoss(string $month): array
    {
        $prevMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        $revenue = $this->revenueByCurrency($month);
        $prevRevenue = $this->revenueByCurrency($prevMonth);

        $cogs = $this->cogsByCurrency($month);
        $prevCogs = $this->cogsByCurrency($prevMonth);

        $expenses = $this->expenseKpiRepo->totalByCurrency($month);
        $prevExpenses = $this->expenseKpiRepo->totalByCurrency($prevMonth);

        $grossProfit = $this->diffByCurrency($revenue, $cogs);
        $prevGrossProfit = $this->diffByCurrency($prevRevenue, $prevCogs);

        $netProfit = $this->diffByCurrency($grossProfit, $expenses);
        $prevNetProfit = $this->diffByCurrency($prevGrossProfit, $prevExpenses);

        return [
            'revenue' => [
                'total' => $revenue,
                'trend' => $this->computeTrend($revenue, $prevRevenue),
            ],
            'cogs' => [
                'total' => $cogs,
                'trend' => $this->computeTrend($cogs, $prevCogs),
            ],
            'grossProfit' => [
                'total' => $grossProfit,
                'trend' => $this->computeTrend($grossProfit, $prevGrossProfit),
            ],
            'expenses' => [
                'total' => $expenses,
                'trend' => $this->computeTrend($expenses, $prevExpenses),
            ],
            'netProfit' => [
                'total' => $netProfit,
                'trend' => $this->computeTrend($netProfit, $prevNetProfit),
            ],
        ];
    }

    private function revenueByCurrency(string $month): array
    {
        return SaleOrder::query()
            ->selectRaw('sale_orders.sls_coin, SUM(sale_orders.sls_total) as total')
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->whereRaw("TO_CHAR(sale_orders.sls_date, 'YYYY-MM') = ?", [$month])
            ->groupBy('sale_orders.sls_coin')
            ->pluck('total', 'sls_coin')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function cogsByCurrency(string $month): array
    {
        return SaleOrder::query()
            ->selectRaw('sale_orders.sls_coin, SUM(movements_sales.mvs_quantity * movements_sales.mvs_unit_price_ref) as total')
            ->join('movements_sales', function ($join) {
                $join->on('sale_orders.sls_number', '=', 'movements_sales.mvs_number')
                    ->on('sale_orders.sls_trans_type_code', '=', 'movements_sales.mvs_trans_type_code');
            })
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->where('movements_sales.mvs_factor', '-1')
            ->whereRaw("TO_CHAR(sale_orders.sls_date, 'YYYY-MM') = ?", [$month])
            ->groupBy('sale_orders.sls_coin')
            ->pluck('total', 'sls_coin')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function diffByCurrency(array $positive, array $negative): array
    {
        $currencies = array_unique([...array_keys($positive), ...array_keys($negative)]);
        $diff = [];

        foreach ($currencies as $currency) {
            $diff[$currency] = ($positive[$currency] ?? 0) - ($negative[$currency] ?? 0);
        }

        return $diff;
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
