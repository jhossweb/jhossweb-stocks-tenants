<?php

namespace App\Services\Dashboard;

use App\Concerns\ResolvesMonthRange;
use App\Models\SaleOrder;
use App\Repositories\Dashboard\ExpenseKpiRepository;
use App\Services\Finance\RateService;

class ProfitLossService
{
    use ResolvesMonthRange;

    public function __construct(
        protected ExpenseKpiRepository $expenseKpiRepo,
        protected RateService $rateService,
    ) {}

    public function getProfitLoss(string $month): array
    {
        $prevMonth = $this->previousMonth($month);

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
                'trend' => $this->trend($revenue, $prevRevenue),
            ],
            'cogs' => [
                'total' => $cogs,
                'trend' => $this->trend($cogs, $prevCogs),
            ],
            'grossProfit' => [
                'total' => $grossProfit,
                'trend' => $this->trend($grossProfit, $prevGrossProfit),
            ],
            'expenses' => [
                'total' => $expenses,
                'trend' => $this->trend($expenses, $prevExpenses),
            ],
            'netProfit' => [
                'total' => $netProfit,
                'trend' => $this->trend($netProfit, $prevNetProfit),
            ],
        ];
    }

    private function revenueByCurrency(string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return SaleOrder::query()
            ->selectRaw('sale_orders.sls_coin, SUM(sale_orders.sls_total) as total')
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->where('sale_orders.sls_date', '>=', $from)
            ->where('sale_orders.sls_date', '<', $to)
            ->groupBy('sale_orders.sls_coin')
            ->pluck('total', 'sls_coin')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function cogsByCurrency(string $month): array
    {
        [$from, $to] = $this->monthBounds($month);

        return SaleOrder::query()
            ->selectRaw('sale_orders.sls_coin, SUM(movements_sales.mvs_quantity * movements_sales.mvs_unit_price_ref) as total')
            ->join('movements_sales', function ($join) {
                $join->on('sale_orders.sls_number', '=', 'movements_sales.mvs_number')
                    ->on('sale_orders.sls_trans_type_code', '=', 'movements_sales.mvs_trans_type_code');
            })
            ->where('sale_orders.sls_trans_type_code', 'FACV')
            ->where('movements_sales.mvs_factor', '-1')
            ->where('sale_orders.sls_date', '>=', $from)
            ->where('sale_orders.sls_date', '<', $to)
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

    /**
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array{direction: string, percentage: float, reliable: bool}
     */
    private function trend(array $current, array $previous): array
    {
        return TrendCalculator::compute($current, $previous, $this->rateService->getLatestRate());
    }
}
