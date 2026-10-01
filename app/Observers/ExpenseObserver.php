<?php

namespace App\Observers;

use App\Models\BankTransaction;
use App\Models\Expense;
use App\Services\Dashboard\DashboardCacheInvalidator;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        $this->forgetDashboardCache($expense);
    }

    public function updated(Expense $expense): void
    {
        $this->forgetDashboardCache($expense);
    }

    public function deleted(Expense $expense): void
    {
        BankTransaction::where('btr_transactionable_id', $expense->id)
            ->where('btr_transactionable_type', Expense::class)
            ->delete();

        $this->forgetDashboardCache($expense);
    }

    private function forgetDashboardCache(Expense $expense): void
    {
        $month = $expense->exp_date?->format('Y-m');

        DashboardCacheInvalidator::expenses($month);
        DashboardCacheInvalidator::profitLoss($month);
    }
}
