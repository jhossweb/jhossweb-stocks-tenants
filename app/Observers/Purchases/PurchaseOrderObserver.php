<?php

namespace App\Observers\Purchases;

use App\Enum\Finanzas\TypePayment;
use App\Enum\Purchases\StatusPurchases;
use App\Models\AccountsPayable;
use App\Models\PurchaseOrder;

class PurchaseOrderObserver
{
    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $purchaseOrder): void
    {
        if (strtoupper($purchaseOrder->invoice_trans_type_code) !== 'FAC') {
            return;
        }

        $accountsPayable = AccountsPayable::create([
            'acp_purchase_number' => $purchaseOrder->invoice_number,
            'acp_total' => $purchaseOrder->invoice_total,
            'acp_balance' => $purchaseOrder->invoice_total,
            'acp_status' => StatusPurchases::PENDING->value,
        ]);

        $this->applyPreviousCredits($purchaseOrder, $accountsPayable);
    }

    private function applyPreviousCredits(PurchaseOrder $purchaseOrder, AccountsPayable $newAp): void
    {
        $supplierId = $purchaseOrder->invoice_supplier_id;

        $creditTotal = AccountsPayable::whereHas('purchaseOrder', fn ($q) => $q->where('invoice_supplier_id', $supplierId)
        )
            ->where('acp_balance', '<', 0)
            ->where('id', '!=', $newAp->id)
            ->sum('acp_balance');

        $creditTotal = abs($creditTotal);

        if ($creditTotal <= 0) {
            return;
        }

        // Cero las APs viejas con saldo negativo
        AccountsPayable::whereHas('purchaseOrder', fn ($q) => $q->where('invoice_supplier_id', $supplierId)
        )
            ->where('acp_balance', '<', 0)
            ->where('id', '!=', $newAp->id)
            ->each(function (AccountsPayable $ap) {
                $ap->acp_balance = 0;
                $ap->acp_status = StatusPurchases::PAID->value;
                $ap->save();

                if ($ap->purchaseOrder) {
                    $ap->purchaseOrder->invoice_status = $ap->acp_status;
                    $ap->purchaseOrder->save();
                }
            });

        // Aplico el crédito en la nueva AP
        $newAp->registerPayment(
            $creditTotal,
            TypePayment::RETURN_CREDIT->value,
            null,
            'abono_credito_anterior'
        );
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
