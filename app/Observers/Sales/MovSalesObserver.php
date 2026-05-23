<?php

namespace App\Observers\Sales;

use App\Models\MovementsSale;
use App\Models\Product;
use DB;

class MovSalesObserver
{
    public function created(MovementsSale $movement): void
    {
        $transType = strtolower((string) $movement->mvs_trans_type_code);
        if (!in_array($transType, ['facv', 'devv'])) return;

        $cantidadFinal = (float) $movement->mvs_quantity * (int) $movement->mvs_factor;
        $product = Product::where('pro_code', $movement->mvs_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->updateStock($componente->pro_code, $movement->mvs_war_id, $compCantidad);
                $this->checkMinStock($componente->pro_code, $movement->mvs_war_id);
            }
        } else {
            $this->updateStock($movement->mvs_pro_code, $movement->mvs_war_id, $cantidadFinal);
            $this->checkMinStock($movement->mvs_pro_code, $movement->mvs_war_id);
        }
    }

    public function updated(MovementsSale $movementsSale): void
    {
        $oldCant = (float) $movementsSale->getOriginal('mvs_quantity');
        $oldFactor = (int) $movementsSale->getOriginal('mvs_factor');
        $oldWareId = $movementsSale->getOriginal('mvs_war_id');
        $oldTransType = strtolower((string) $movementsSale->getOriginal('mvs_trans_type_code'));

        $product = Product::where('pro_code', $movementsSale->mvs_pro_code)->first();
        $esCompuesto = $product && $product->pro_type?->value === 'compuesto';

        if (in_array($oldTransType, ['facv', 'devv'])) {
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $oldCant * $oldFactor;
                    $this->updateStock($componente->pro_code, $oldWareId, -$compCantidad);
                }
            } else {
                $this->updateStock($movementsSale->mvs_pro_code, $oldWareId, -($oldCant * $oldFactor));
            }
        }
        $newTransType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (in_array($newTransType, ['facv', 'devv'])) {
            $newCantidad = $movementsSale->mvs_quantity * $movementsSale->mvs_factor;
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $newCantidad;
                    $this->updateStock($componente->pro_code, $movementsSale->mvs_war_id, $compCantidad);
                    $this->checkMinStock($componente->pro_code, $movementsSale->mvs_war_id);
                }
            } else {
                $this->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, $newCantidad);
                $this->checkMinStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id);
            }
        }
    }

    public function deleted(MovementsSale $movementsSale): void
    {
        $transType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (!in_array($transType, ['facv', 'devv'])) return;

        $cantidadFinal = -((float) $movementsSale->mvs_quantity * (int) $movementsSale->mvs_factor);
        $product = Product::where('pro_code', $movementsSale->mvs_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->updateStock($componente->pro_code, $movementsSale->mvs_war_id, $compCantidad);
            }
        } else {
            $this->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, $cantidadFinal);
        }
    }

    public function restored(MovementsSale $movementsSale): void {}

    public function forceDeleted(MovementsSale $movementsSale): void {}

    protected function checkMinStock(string $proCode, int $warehouseId): void
    {
        $stock = DB::table('stocks')
            ->where('stk_pro_code', $proCode)
            ->where('stk_war_id', $warehouseId)
            ->first();

        if (! $stock) {
            return;
        }

        $product = Product::where('pro_code', $proCode)->first();
        if (! $product || $product->pro_cant_min <= 0) {
            return;
        }

        if ($stock->stk_cant <= $product->pro_cant_min) {
            session()->flash('warning', "Stock bajo: {$product->pro_name} ({$stock->stk_cant} {$product->pro_und->value})");
        }
    }

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
