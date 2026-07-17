<?php

namespace App\Observers;

use App\Models\BankTransaction;
use App\Models\Expense;
use Illuminate\Support\Facades\Cache;

class ExpenseObserver
{
    private function forgetDashboardCache(Expense $expense): void
    {
        $month = $expense->exp_date?->format('Y-m');
        if (! $month) {
            return;
        }

        $previousMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        Cache::forget("dashboard:expenses:total:{$month}");
        Cache::forget("dashboard:expenses:total:{$previousMonth}");
        Cache::forget('dashboard:expenses:pending');
    }

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
}
