<?php

namespace App\Observers\Purchases;

use App\Models\PurchaseOrder;
use App\Models\AccountsPayable;
use App\Enum\Purchases\StatusPurchases;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $purchaseOrder): void
    {
        // Only if it's a purchase (FAC)
        if (strtoupper($purchaseOrder->invoice_trans_type_code) === 'FAC') {
            AccountsPayable::create([
                'acp_purchase_number' => $purchaseOrder->invoice_number,
                'acp_total' => $purchaseOrder->invoice_total,
                'acp_balance' => $purchaseOrder->invoice_total,
                'acp_status' => StatusPurchases::PENDING->value,
            ]);
        }
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $purchaseOrder): void
    {
        $accountsPayable = $purchaseOrder->accountsPayable;
        if ($accountsPayable) {
            // Update total and adjust balance if total changed
            $difference = $purchaseOrder->invoice_total - $purchaseOrder->getOriginal('invoice_total');
            $accountsPayable->acp_total = $purchaseOrder->invoice_total;
            $accountsPayable->acp_balance += $difference;
            
            // Recalculate status based on balance
            if ($accountsPayable->acp_balance <= 0) {
                $accountsPayable->acp_status = StatusPurchases::PAID->value;
            } elseif ($accountsPayable->acp_balance < $accountsPayable->acp_total) {
                $accountsPayable->acp_status = StatusPurchases::PARTIAL->value;
            } else {
                $accountsPayable->acp_status = StatusPurchases::PENDING->value;
            }

            $accountsPayable->save();
        }
    }

    /**
     * Handle the PurchaseOrder "deleted" event.
     */
    public function deleted(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->accountsPayable()?->delete();
    }
}
