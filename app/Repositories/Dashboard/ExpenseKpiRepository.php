<?php

namespace App\Repositories\Dashboard;

use App\Models\Expense;

class ExpenseKpiRepository
{
    public function totalByCurrency(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_amount) as total, SUM(exp_paid_amount) as paid')
            ->whereRaw("TO_CHAR(exp_date, 'YYYY-MM') = ?", [$month])
            ->groupBy('exp_coin')
            ->pluck('total', 'exp_coin')
            ->toArray();
    }

    public function pendingBalance(): array
    {
        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_remaining_balance) as balance')
            ->whereIn('exp_status', ['pendiente', 'parcial'])
            ->groupBy('exp_coin')
            ->pluck('balance', 'exp_coin')
            ->toArray();
    }

    public function trend(string $month): array
    {
        $previousMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        $current = $this->totalRaw($month);
        $previous = $this->totalRaw($previousMonth);

        return $this->computeTrend($current, $previous);
    }

    private function totalRaw(string $month): array
    {
        return Expense::query()
            ->selectRaw('exp_coin, SUM(exp_amount) as total')
            ->whereRaw("TO_CHAR(exp_date, 'YYYY-MM') = ?", [$month])
            ->groupBy('exp_coin')
            ->pluck('total', 'exp_coin')
            ->toArray();
    }

    public function byCategory(?string $month): array
    {
        $month ??= now()->format('Y-m');

        return Expense::query()
            ->selectRaw('ec.exc_name, ec.exc_type, e.exp_coin, SUM(e.exp_amount) as total')
            ->join('expense_categories as ec', 'e.exp_category_id', '=', 'ec.id')
            ->from('expenses as e')
            ->whereRaw("TO_CHAR(e.exp_date, 'YYYY-MM') = ?", [$month])
            ->groupBy('ec.id', 'e.exp_coin')
            ->orderByDesc('total')
            ->get()
            ->toArray();
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
