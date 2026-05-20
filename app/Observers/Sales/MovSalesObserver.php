<?php

namespace App\Observers\Sales;

use App\Models\MovementsSale;
use DB;

class MovSalesObserver
{
    public function created(MovementsSale $movement): void
    {
        $transType = strtolower((string) $movement->mvs_trans_type_code);
        if (in_array($transType, ['facv', 'devv'])) {
            $cantidadFinal = (float) $movement->mvs_quantity * (int) $movement->mvs_factor;
            $this->updateStock($movement->mvs_pro_code, $movement->mvs_war_id, $cantidadFinal);
        }
    }

    public function updated(MovementsSale $movementsSale): void
    {
        $oldCant = (float) $movementsSale->getOriginal('mvs_quantity');
        $oldFactor = (int) $movementsSale->getOriginal('mvs_factor');
        $oldWareId = $movementsSale->getOriginal('mvs_war_id');
        $oldTransType = strtolower((string) $movementsSale->getOriginal('mvs_trans_type_code'));
        if (in_array($oldTransType, ['facv', 'devv'])) {
            $this->updateStock($movementsSale->mvs_pro_code, $oldWareId, -($oldCant * $oldFactor));
        }
        $newTransType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (in_array($newTransType, ['facv', 'devv'])) {
            $this->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, ($movementsSale->mvs_quantity * $movementsSale->mvs_factor));
        }
    }

    public function deleted(MovementsSale $movementsSale): void
    {
        $transType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (in_array($transType, ['facv', 'devv'])) {
            $this->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, -((float) $movementsSale->mvs_quantity * (int) $movementsSale->mvs_factor));
        }
    }

    public function restored(MovementsSale $movementsSale): void {}

    public function forceDeleted(MovementsSale $movementsSale): void {}

    protected function updateStock($proCode, $warehouseId, $amount)
    {
        $currentStock = DB::table('stocks')
            ->where('stk_pro_code', $proCode)
            ->where('stk_war_id', $warehouseId)
            ->value('stk_cant') ?? 0;

        DB::table('stocks')->updateOrInsert(
            ['stk_pro_code' => $proCode, 'stk_war_id' => $warehouseId],
            [
                'stk_cant' => $currentStock + $amount,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
