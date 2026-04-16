<?php

namespace App\Observers\Purchases;

use App\Models\MovementsPurchase;
use DB;

class MovPurchasesObserver
{
    /**
     * Handle the MovementsPurchase "created" event.
     */
    public function created(MovementsPurchase $movement): void
    {
        $cantidadFinal = (float) $movement->mov_quantity * (int) $movement->mov_factor;

        $this->updateStock(
            $movement->mov_pro_code,
            $movement->mov_war_id,
            $cantidadFinal
        );
    }

    /**
     * Handle the MovementsPurchase "updated" event.
     */
    public function updated(MovementsPurchase $movementsPurchase): void
    {
        $oldCant = $movementsPurchase->getOriginal('mov_quantity');
        $oldFactor = $movementsPurchase->getOriginal('mov_factor');
        $oldWareId = $movementsPurchase->getOriginal('mov_war_id');

        $this->updateStock(
            $movementsPurchase->mov_pro_code,
            $oldWareId,
            -($oldCant * (int) $oldFactor)
        );

        $this->updateStock(
            $movementsPurchase->mov_pro_code,
            $movementsPurchase->mov_war_id,
            ($movementsPurchase->mov_quantity * (int) $movementsPurchase->mov_factor)
        );
    }

    /**
     * Handle the MovementsPurchase "deleted" event.
     */
    public function deleted(MovementsPurchase $movementsPurchase): void
    {
        $this->updateStock(
            $movementsPurchase->mov_pro_code,
            $movementsPurchase->mov_war_id,
            -($movementsPurchase->mov_quantity * (int) $movementsPurchase->mov_factor)
        );
    }

    /**
     * Handle the MovementsPurchase "restored" event.
     */
    public function restored(MovementsPurchase $movementsPurchase): void
    {
        //
    }

    /**
     * Handle the MovementsPurchase "force deleted" event.
     */
    public function forceDeleted(MovementsPurchase $movementsPurchase): void
    {
        //
    }


    protected function updateStock($proCode, $warehouseId, $amount)
    {
        // Usamos updateOrInsert para asegurar que si no existe lo cree, 
        // y si existe, sume al valor actual.

        $currentStock = DB::table('stocks')
            ->where('stk_pro_code', $proCode)
            ->where('stk_war_id', $warehouseId)
            ->value('stk_cant') ?? 0;

        DB::table('stocks')->updateOrInsert(
            ['stk_pro_code' => $proCode, 'stk_war_id' => $warehouseId],
            [
                'stk_cant' => $currentStock + $amount,
                'updated_at' => now()
            ]
        );
    }
}
