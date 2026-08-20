<?php

namespace App\Observers\Purchases;

use App\Models\MovementsPurchase;
use App\Services\Inventory\StockService;

class MovPurchasesObserver
{
    public function __construct(protected StockService $stockService) {}

    public function created(MovementsPurchase $movement): void
    {
        $transType = strtolower((string) $movement->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            $factor = $this->stockService->getConversionFactor($movement->mov_pro_code);
            $cantidadFinal = (float) $movement->mov_quantity * (int) $movement->mov_factor * $factor;

            $this->stockService->updateStock(
                $movement->mov_pro_code,
                $movement->mov_war_id,
                $cantidadFinal
            );
        }
    }

    public function updated(MovementsPurchase $movementsPurchase): void
    {
        $oldCant = (float) $movementsPurchase->getOriginal('mov_quantity');
        $oldFactor = (int) $movementsPurchase->getOriginal('mov_factor');
        $oldWareId = $movementsPurchase->getOriginal('mov_war_id');
        $oldTransType = strtolower((string) $movementsPurchase->getOriginal('mov_invoice_trans_type_code'));
        $proCode = $movementsPurchase->mov_pro_code;
        $conversion = $this->stockService->getConversionFactor($proCode);

        if (in_array($oldTransType, ['fac', 'devc'])) {
            $this->stockService->updateStock($proCode, $oldWareId, -(($oldCant * $oldFactor) * $conversion));
        }

        $newTransType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);
        if (in_array($newTransType, ['fac', 'devc'])) {
            $this->stockService->updateStock(
                $proCode,
                $movementsPurchase->mov_war_id,
                ($movementsPurchase->mov_quantity * $movementsPurchase->mov_factor) * $conversion
            );
        }
    }

    public function deleted(MovementsPurchase $movementsPurchase): void
    {
        $transType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            $factor = $this->stockService->getConversionFactor($movementsPurchase->mov_pro_code);
            $this->stockService->updateStock(
                $movementsPurchase->mov_pro_code,
                $movementsPurchase->mov_war_id,
                -((float) $movementsPurchase->mov_quantity * abs((int) $movementsPurchase->mov_factor) * $factor)
            );
        }
    }

    public function restored(MovementsPurchase $movementsPurchase): void
    {
        //
    }

    public function forceDeleted(MovementsPurchase $movementsPurchase): void
    {
        //
    }
}
