<?php

namespace App\Observers\Inventory;

use App\Models\MovementInventory;
use App\Models\Product;
use DB;

class MovInventoryObserver
{
    public function created(MovementInventory $movement): void
    {
        $transType = strtolower((string) $movement->min_trans_type_code);
        if ($transType !== 'inv') {
            return;
        }

        $cantidadFinal = (float) $movement->min_quantity * (int) $movement->min_factor;
        $product = Product::where('pro_code', $movement->min_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
                $this->checkMinStock($componente->pro_code, $movement->min_war_id);
            }
        } else {
            $this->updateStock($movement->min_pro_code, $movement->min_war_id, $cantidadFinal);
            $this->checkMinStock($movement->min_pro_code, $movement->min_war_id);
        }
    }

    public function updated(MovementInventory $movement): void
    {
        $oldCant = (float) $movement->getOriginal('min_quantity');
        $oldFactor = (int) $movement->getOriginal('min_factor');
        $oldWareId = $movement->getOriginal('min_war_id');
        $oldTransType = strtolower((string) $movement->getOriginal('min_trans_type_code'));

        $product = Product::where('pro_code', $movement->min_pro_code)->first();
        $esCompuesto = $product && $product->pro_type?->value === 'compuesto';

        if ($oldTransType === 'inv') {
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $oldCant * $oldFactor;
                    $this->updateStock($componente->pro_code, $oldWareId, -$compCantidad);
                }
            } else {
                $this->updateStock($movement->min_pro_code, $oldWareId, -($oldCant * $oldFactor));
            }
        }

        $newTransType = strtolower((string) $movement->min_trans_type_code);
        if ($newTransType === 'inv') {
            $newCantidad = $movement->min_quantity * $movement->min_factor;
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $newCantidad;
                    $this->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
                    $this->checkMinStock($componente->pro_code, $movement->min_war_id);
                }
            } else {
                $this->updateStock($movement->min_pro_code, $movement->min_war_id, $newCantidad);
                $this->checkMinStock($movement->min_pro_code, $movement->min_war_id);
            }
        }
    }

    public function deleted(MovementInventory $movement): void
    {
        $transType = strtolower((string) $movement->min_trans_type_code);
        if ($transType !== 'inv') {
            return;
        }

        $cantidadFinal = -((float) $movement->min_quantity * (int) $movement->min_factor);
        $product = Product::where('pro_code', $movement->min_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
            }
        } else {
            $this->updateStock($movement->min_pro_code, $movement->min_war_id, $cantidadFinal);
        }
    }

    public function restored(MovementInventory $movement): void {}

    public function forceDeleted(MovementInventory $movement): void {}

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
