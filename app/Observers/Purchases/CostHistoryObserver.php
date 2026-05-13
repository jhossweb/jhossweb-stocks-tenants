<?php

namespace App\Observers\Purchases;

use App\Models\CostHistory;
use App\Models\MovementsPurchase;

class CostHistoryObserver
{
    public function created(MovementsPurchase $movement): void
    {
        $this->registerCostHistory($movement, 'CREATED');
    }

    public function updated(MovementsPurchase $movement): void
    {
        $this->registerCostHistory($movement, 'UPDATED');
    }

    public function deleted(MovementsPurchase $movement): void
    {
    }

    public function restored(MovementsPurchase $movement): void
    {
    }

    public function forceDeleted(MovementsPurchase $movement): void
    {
    }

    protected function registerCostHistory(MovementsPurchase $movement, string $eventType): void
    {
        $purchase = $movement->purchaseOrder;

        if (!$purchase) {
            return;
        }

        CostHistory::create([
            'cost_pro_code' => $movement->mov_pro_code,
            'cost_pro_cost' => $movement->mov_unit_cost,
            'cost_pro_cost_ref' => $movement->mov_unit_cost_ref,
            'cost_valorcam' => $purchase->invoice_valorcam,
            'cost_currency' => $purchase->invoice_coin,
            'cost_date' => $purchase->invoice_date,
            'cost_user_id' => auth()->check() ? auth()->id() : null,
        ]);
    }
}
