<?php

namespace App\Observers\Purchases;

use App\Models\MovementsPurchase;
use DB;

class MovPurchasesObserver
{
    /**
     * Handle the MovementsPurchase "created" event.
     *
     * Reglas de negocio:
     *  - FAC  (factor =  1): Factura de compra → la mercancía ENTRA al inventario (+).
     *  - DEVC (factor = -1): Devolución de compra → el proveedor devuelve mercancía,
     *                         por lo tanto también ENTRA al inventario (+).
     *
     * Ambos tipos siempre incrementan el stock; el signo del factor NO invierte la
     * dirección, sólo identifica el tipo de transacción.
     */
    public function created(MovementsPurchase $movement): void
    {
        $transType = strtolower((string) $movement->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            // Siempre positivo: ambos tipos aumentan inventario
            $cantidadFinal = (float) $movement->mov_quantity * (int) $movement->mov_factor;

            $this->updateStock(
                $movement->mov_pro_code,
                $movement->mov_war_id,
                $cantidadFinal
            );
        }
    }

    /**
     * Handle the MovementsPurchase "updated" event.
     *
     * Revierte el movimiento anterior (resta el valor absoluto) y aplica el nuevo
     * movimiento (suma el valor absoluto), respetando la misma lógica de "created":
     * FAC y DEVC siempre suman stock, por lo que al revertir siempre restan.
     */
    public function updated(MovementsPurchase $movementsPurchase): void
    {
        $oldCant = (float) $movementsPurchase->getOriginal('mov_quantity');
        $oldFactor = (int) $movementsPurchase->getOriginal('mov_factor');
        $oldWareId = $movementsPurchase->getOriginal('mov_war_id');
        $oldTransType = strtolower((string) $movementsPurchase->getOriginal('mov_invoice_trans_type_code'));

        // Revertir el movimiento anterior (siempre negativo para FAC/DEVC)
        if (in_array($oldTransType, ['fac', 'devc'])) {
            $this->updateStock(
                $movementsPurchase->mov_pro_code,
                $oldWareId,
                -($oldCant * $oldFactor)
            );
        }

        // Aplicar el nuevo movimiento
        $newTransType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);
        if (in_array($newTransType, ['fac', 'devc'])) {
            $this->updateStock(
                $movementsPurchase->mov_pro_code,
                $movementsPurchase->mov_war_id,
                ($movementsPurchase->mov_quantity * $movementsPurchase->mov_factor)
            );
        }
    }

    /**
     * Handle the MovementsPurchase "deleted" event.
     *
     * Al eliminar un movimiento FAC o DEVC se revierte el efecto (se resta del inventario).
     */
    public function deleted(MovementsPurchase $movementsPurchase): void
    {
        $transType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            $this->updateStock(
                $movementsPurchase->mov_pro_code,
                $movementsPurchase->mov_war_id,
                -((float) $movementsPurchase->mov_quantity * abs((int) $movementsPurchase->mov_factor))
            );
        }
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
                'updated_at' => now(),
                'created_at' => now(), // solo aplica en INSERT; UPDATE lo ignora
            ]
        );
    }
}
