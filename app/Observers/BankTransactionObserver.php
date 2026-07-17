<?php

namespace App\Observers;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Cache;

class BankTransactionObserver
{
    private function forgetDashboardCache(string $month): void
    {
        $prevMonth = date('Y-m', strtotime("{$month}-01 -1 month"));

        Cache::forget("dashboard:cashflow:inflow:{$month}");
        Cache::forget("dashboard:cashflow:outflow:{$month}");
        Cache::forget("dashboard:cashflow:inflow:{$prevMonth}");
        Cache::forget("dashboard:cashflow:outflow:{$prevMonth}");
    }

    private function forgetDashboardCacheForTransaction(BankTransaction $bankTransaction): void
    {
        $month = $bankTransaction->btr_transaction_date?->format('Y-m');
        if ($month) {
            $this->forgetDashboardCache($month);
        }
    }

    public function creating(BankTransaction $bankTransaction): void
    {
        if ($bankTransaction->btr_type === 'outflow') {
            $account = $bankTransaction->bankAccount;
            if (! $account || $account->bank_balance < $bankTransaction->btr_amount) {
                throw new \RuntimeException(
                    'Saldo insuficiente en la cuenta bancaria. '
                    .'Saldo actual: '.number_format((float) ($account?->bank_balance ?? 0), 2)
                    .', requiere: '.number_format((float) $bankTransaction->btr_amount, 2)
                );
            }
        }
    }

    public function created(BankTransaction $bankTransaction): void
    {
        $account = $bankTransaction->bankAccount;

        if ($bankTransaction->btr_type === 'inflow') {
            $account->bank_balance += $bankTransaction->btr_amount;
        } else {
            $account->bank_balance -= $bankTransaction->btr_amount;
        }

        $account->save();

        $bankTransaction->updateQuietly([
            'btr_balance' => $account->bank_balance,
        ]);

        $this->forgetDashboardCacheForTransaction($bankTransaction);
    }

    public function updated(BankTransaction $bankTransaction): void
    {
        $oldMonth = $bankTransaction->getOriginal('btr_transaction_date')
            ? date('Y-m', strtotime((string) $bankTransaction->getOriginal('btr_transaction_date')))
            : null;

        if ($oldMonth) {
            $this->forgetDashboardCache($oldMonth);
        }

        $this->forgetDashboardCacheForTransaction($bankTransaction);

        if ($bankTransaction->wasChanged(['btr_amount', 'btr_type', 'btr_bank_account_id'])) {

            if ($bankTransaction->wasChanged('btr_bank_account_id')) {
                $oldAccountId = $bankTransaction->getOriginal('btr_bank_account_id');
                $oldAccount = BankAccount::find($oldAccountId);

                if ($oldAccount) {
                    $oldAmount = $bankTransaction->getOriginal('btr_amount');
                    $oldType = $bankTransaction->getOriginal('btr_type');

                    if ($oldType === 'inflow') {
                        $oldAccount->bank_balance -= $oldAmount;
                    } else {
                        $oldAccount->bank_balance += $oldAmount;
                    }
                    $oldAccount->save();
                }

                $newAccount = $bankTransaction->bankAccount;
                if ($bankTransaction->btr_type === 'inflow') {
                    $newAccount->bank_balance += $bankTransaction->btr_amount;
                } else {
                    $newAccount->bank_balance -= $bankTransaction->btr_amount;
                }
                $newAccount->save();
            } else {
                $account = $bankTransaction->bankAccount;

                $oldAmount = $bankTransaction->getOriginal('btr_amount');
                $oldType = $bankTransaction->getOriginal('btr_type');

                if ($oldType === 'inflow') {
                    $account->bank_balance -= $oldAmount;
                } else {
                    $account->bank_balance += $oldAmount;
                }

                if ($bankTransaction->btr_type === 'inflow') {
                    $account->bank_balance += $bankTransaction->btr_amount;
                } else {
                    $account->bank_balance -= $bankTransaction->btr_amount;
                }

                $account->save();
            }

            $bankTransaction->updateQuietly([
                'btr_balance' => $bankTransaction->bankAccount->bank_balance,
            ]);
        }
    }

    public function deleted(BankTransaction $bankTransaction): void
    {
        $account = $bankTransaction->bankAccount;

        if ($account) {
            if ($bankTransaction->btr_type === 'inflow') {
                $account->bank_balance -= $bankTransaction->btr_amount;
            } else {
                $account->bank_balance += $bankTransaction->btr_amount;
            }

            $account->save();
        }

        $this->forgetDashboardCacheForTransaction($bankTransaction);
    }
}
