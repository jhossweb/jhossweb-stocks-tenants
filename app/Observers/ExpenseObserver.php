<?php

namespace App\Observers;

use App\Models\BankTransaction;
use App\Models\Expense;

class ExpenseObserver
{
    public function deleted(Expense $expense): void
    {
        BankTransaction::where('btr_transactionable_id', $expense->id)
            ->where('btr_transactionable_type', Expense::class)
            ->delete();
    }
}
