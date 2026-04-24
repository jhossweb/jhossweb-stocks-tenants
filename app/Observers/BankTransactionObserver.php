<?php

namespace App\Observers;

use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

class BankTransactionObserver
{
    /**
     * Handle the BankTransaction "created" event.
     */
    public function created(BankTransaction $bankTransaction): void
    {
        $account = $bankTransaction->bankAccount;
        
        // Update account balance
        if ($bankTransaction->btr_type === 'inflow') {
            $account->bank_balance += $bankTransaction->btr_amount;
        } else {
            $account->bank_balance -= $bankTransaction->btr_amount;
        }
        
        $account->save();
        
        // Save the running balance in the transaction itself
        $bankTransaction->updateQuietly([
            'btr_balance' => $account->bank_balance
        ]);
    }

    /**
     * Handle the BankTransaction "deleted" event.
     */
    public function deleted(BankTransaction $bankTransaction): void
    {
        $account = $bankTransaction->bankAccount;
        
        // Reverse account balance update
        if ($bankTransaction->btr_type === 'inflow') {
            $account->bank_balance -= $bankTransaction->btr_amount;
        } else {
            $account->bank_balance += $bankTransaction->btr_amount;
        }
        
        $account->save();
    }
}
