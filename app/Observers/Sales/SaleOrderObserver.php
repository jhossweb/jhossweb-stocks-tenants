<?php

namespace App\Observers\Sales;

use App\Models\SaleOrder;
use App\Models\AccountsReceivable;
use App\Enum\Sales\StatusSales;

class SaleOrderObserver
{
    public function created(SaleOrder $saleOrder): void
    {
        if (strtoupper($saleOrder->sls_trans_type_code) === 'FAVC') {
            AccountsReceivable::create([
                'acr_sls_number' => $saleOrder->sls_number,
                'acr_total' => $saleOrder->sls_total,
                'acr_balance' => $saleOrder->sls_total,
                'acr_status' => StatusSales::PENDING->value,
            ]);
        }
    }

    public function updated(SaleOrder $saleOrder): void
    {
        $accountsReceivable = $saleOrder->accountsReceivable;
        if ($accountsReceivable) {
            $difference = $saleOrder->sls_total - $saleOrder->getOriginal('sls_total');
            $accountsReceivable->acr_total = $saleOrder->sls_total;
            $accountsReceivable->acr_balance += $difference;
            if ($accountsReceivable->acr_balance <= 0) {
                $accountsReceivable->acr_status = StatusSales::PAID->value;
            } elseif ($accountsReceivable->acr_balance < $accountsReceivable->acr_total) {
                $accountsReceivable->acr_status = StatusSales::PARTIAL->value;
            } else {
                $accountsReceivable->acr_status = StatusSales::PENDING->value;
            }
            $accountsReceivable->save();
        }
    }

    public function deleted(SaleOrder $saleOrder): void
    {
        $saleOrder->accountsReceivable()?->delete();
    }
}
