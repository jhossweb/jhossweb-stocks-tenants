<?php

namespace App\Observers\Sales;

use App\Models\MovementsSale;
use App\Models\Product;
use App\Services\Inventory\StockService;

class MovSalesObserver
{
    public function __construct(protected StockService $stockService) {}

    public function created(MovementsSale $movement): void
    {
        $transType = strtolower((string) $movement->mvs_trans_type_code);
        if (! in_array($transType, ['facv', 'devv'])) {
            return;
        }

        $cantidadFinal = (float) $movement->mvs_quantity * (int) $movement->mvs_factor;
        $product = Product::where('pro_code', $movement->mvs_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->stockService->updateStock($componente->pro_code, $movement->mvs_war_id, $compCantidad);
                $this->stockService->addLowStockWarning($componente->pro_code, $movement->mvs_war_id);
            }
        } else {
            $this->stockService->updateStock($movement->mvs_pro_code, $movement->mvs_war_id, $cantidadFinal);
            $this->stockService->addLowStockWarning($movement->mvs_pro_code, $movement->mvs_war_id);
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
                    $this->stockService->updateStock($componente->pro_code, $oldWareId, -$compCantidad);
                }
            } else {
                $this->stockService->updateStock($movementsSale->mvs_pro_code, $oldWareId, -($oldCant * $oldFactor));
            }
        }
        $newTransType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (in_array($newTransType, ['facv', 'devv'])) {
            $newCantidad = $movementsSale->mvs_quantity * $movementsSale->mvs_factor;
            if ($esCompuesto && $product) {
                foreach ($product->componentes as $componente) {
                    $compCantidad = $componente->pivot->cp_quantity * $newCantidad;
                    $this->stockService->updateStock($componente->pro_code, $movementsSale->mvs_war_id, $compCantidad);
                    $this->stockService->addLowStockWarning($componente->pro_code, $movementsSale->mvs_war_id);
                }
            } else {
                $this->stockService->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, $newCantidad);
                $this->stockService->addLowStockWarning($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id);
            }
        }
    }

    public function deleted(MovementsSale $movementsSale): void
    {
        $transType = strtolower((string) $movementsSale->mvs_trans_type_code);
        if (! in_array($transType, ['facv', 'devv'])) {
            return;
        }

        $cantidadFinal = -((float) $movementsSale->mvs_quantity * (int) $movementsSale->mvs_factor);
        $product = Product::where('pro_code', $movementsSale->mvs_pro_code)->first();

        if ($product && $product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $compCantidad = $componente->pivot->cp_quantity * $cantidadFinal;
                $this->stockService->updateStock($componente->pro_code, $movementsSale->mvs_war_id, $compCantidad);
            }
        } else {
            $this->stockService->updateStock($movementsSale->mvs_pro_code, $movementsSale->mvs_war_id, $cantidadFinal);
        }
    }

    public function restored(MovementsSale $movementsSale): void {}

    public function forceDeleted(MovementsSale $movementsSale): void {}
}
