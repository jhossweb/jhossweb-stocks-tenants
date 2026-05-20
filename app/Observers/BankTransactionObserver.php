<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;

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
            'btr_balance' => $account->bank_balance,
        ]);
    }

    /**
     * Handle the BankTransaction "updated" event.
     */
    public function updated(BankTransaction $bankTransaction): void
    {
        // Check if important fields changed
        if ($bankTransaction->wasChanged(['btr_amount', 'btr_type', 'btr_bank_account_id'])) {

            // If the account changed, we need to adjust both the old and new accounts
            if ($bankTransaction->wasChanged('btr_bank_account_id')) {
                $oldAccountId = $bankTransaction->getOriginal('btr_bank_account_id');
                $oldAccount = BankAccount::find($oldAccountId);

                if ($oldAccount) {
                    // Reverse the original transaction from the old account
                    $oldAmount = $bankTransaction->getOriginal('btr_amount');
                    $oldType = $bankTransaction->getOriginal('btr_type');

                    if ($oldType === 'inflow') {
                        $oldAccount->bank_balance -= $oldAmount;
                    } else {
                        $oldAccount->bank_balance += $oldAmount;
                    }
                    $oldAccount->save();
                }

                // Apply the new transaction to the new account
                $newAccount = $bankTransaction->bankAccount;
                if ($bankTransaction->btr_type === 'inflow') {
                    $newAccount->bank_balance += $bankTransaction->btr_amount;
                } else {
                    $newAccount->bank_balance -= $bankTransaction->btr_amount;
                }
                $newAccount->save();
            } else {
                // Same account, just adjust by the delta
                $account = $bankTransaction->bankAccount;

                // Reverse old values
                $oldAmount = $bankTransaction->getOriginal('btr_amount');
                $oldType = $bankTransaction->getOriginal('btr_type');

                if ($oldType === 'inflow') {
                    $account->bank_balance -= $oldAmount;
                } else {
                    $account->bank_balance += $oldAmount;
                }

                // Apply new values
                if ($bankTransaction->btr_type === 'inflow') {
                    $account->bank_balance += $bankTransaction->btr_amount;
                } else {
                    $account->bank_balance -= $bankTransaction->btr_amount;
                }

                $account->save();
            }

            // Sync the running balance in the transaction record
            $bankTransaction->updateQuietly([
                'btr_balance' => $bankTransaction->bankAccount->bank_balance,
            ]);
        }
    }

    /**
     * Handle the BankTransaction "deleted" event.
     */
    public function deleted(BankTransaction $bankTransaction): void
    {
        $account = $bankTransaction->bankAccount;

        if ($account) {
            // Reverse account balance update
            if ($bankTransaction->btr_type === 'inflow') {
                $account->bank_balance -= $bankTransaction->btr_amount;
            } else {
                $account->bank_balance += $bankTransaction->btr_amount;
            }

            $account->save();
        }
    }
}
