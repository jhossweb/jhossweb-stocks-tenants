<?php

namespace App\Observers\Inventory;

use App\Models\MovementInventory;
use App\Models\Product;
use App\Services\Inventory\StockService;

class MovInventoryObserver
{
    public function __construct(protected StockService $stockService) {}

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
                $this->stockService->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
                $this->stockService->addLowStockWarning($componente->pro_code, $movement->min_war_id);
            }
        } else {
            $this->stockService->updateStock($movement->min_pro_code, $movement->min_war_id, $cantidadFinal);
            $this->stockService->addLowStockWarning($movement->min_pro_code, $movement->min_war_id);
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
                    $this->stockService->updateStock($componente->pro_code, $oldWareId, -$compCantidad);
                }
            } else {
                $this->stockService->updateStock($movement->min_pro_code, $oldWareId, -($oldCant * $oldFactor));
            }
        }

        $newTransType = strtolower((string) $movement->min_trans_type_code);
        if ($newTransType === 'inv') {
            $newCantidad = $movement->min_quantity * $movement->min_factor;
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $newCantidad;
                    $this->stockService->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
                    $this->stockService->addLowStockWarning($componente->pro_code, $movement->min_war_id);
                }
            } else {
                $this->stockService->updateStock($movement->min_pro_code, $movement->min_war_id, $newCantidad);
                $this->stockService->addLowStockWarning($movement->min_pro_code, $movement->min_war_id);
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
                $this->stockService->updateStock($componente->pro_code, $movement->min_war_id, $compCantidad);
            }
        } else {
            $this->stockService->updateStock($movement->min_pro_code, $movement->min_war_id, $cantidadFinal);
        }
    }

    public function restored(MovementInventory $movement): void {}

    public function forceDeleted(MovementInventory $movement): void {}
}
