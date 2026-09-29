<?php

declare(strict_types=1);

namespace App\Observers\Inventory;

use App\Models\MovementInventory;
use App\Models\Product;
use App\Services\Inventory\StockService;

class MovInventoryObserver
{
    public function __construct(protected StockService $stockService) {}

    public function created(MovementInventory $movement): void
    {
        if (! $this->isInventoryMovement($movement->min_trans_type_code)) {
            return;
        }

        $this->applyStock(
            $movement->min_pro_code,
            (int) $movement->min_war_id,
            (float) $movement->min_quantity * (int) $movement->min_factor,
        );
    }

    /**
     * Revierte el stock del estado anterior y aplica el nuevo.
     *
     * La reversión usa SIEMPRE el producto/almacén ORIGINALES: usar el
     * `min_pro_code` nuevo devolvía el stock al producto equivocado cuando un
     * renglón existente cambiaba de producto.
     */
    public function updated(MovementInventory $movement): void
    {
        $wasInventoryMovement = $this->isInventoryMovement($movement->getOriginal('min_trans_type_code'));

        if ($wasInventoryMovement) {
            $this->applyStock(
                (string) $movement->getOriginal('min_pro_code'),
                (int) $movement->getOriginal('min_war_id'),
                -((float) $movement->getOriginal('min_quantity') * (int) $movement->getOriginal('min_factor')),
                warn: false,
            );
        }

        if ($this->isInventoryMovement($movement->min_trans_type_code)) {
            $this->applyStock(
                (string) $movement->min_pro_code,
                (int) $movement->min_war_id,
                (float) $movement->min_quantity * (int) $movement->min_factor,
            );
        }
    }

    public function deleted(MovementInventory $movement): void
    {
        if (! $this->isInventoryMovement($movement->min_trans_type_code)) {
            return;
        }

        $this->applyStock(
            (string) $movement->min_pro_code,
            (int) $movement->min_war_id,
            -((float) $movement->min_quantity * (int) $movement->min_factor),
            warn: false,
        );
    }

    public function restored(MovementInventory $movement): void {}

    public function forceDeleted(MovementInventory $movement): void {}

    private function isInventoryMovement(mixed $transTypeCode): bool
    {
        return strtolower((string) $transTypeCode) === 'inv';
    }

    /**
     * Aplica stock a un producto. Los compuestos mueven el stock de sus
     * componentes (el producto compuesto es virtual y no se stockedea).
     */
    private function applyStock(string $proCode, int $warehouseId, float $amount, bool $warn = true): void
    {
        $product = Product::with('componentes')->where('pro_code', $proCode)->first();

        if (! $product) {
            return;
        }

        if ($product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $componente) {
                $this->stockService->updateStock(
                    $componente->pro_code,
                    $warehouseId,
                    $componente->pivot->cp_quantity * $amount,
                );
                if ($warn) {
                    $this->stockService->addLowStockWarning($componente->pro_code, $warehouseId);
                }
            }

            return;
        }

        $this->stockService->updateStock($proCode, $warehouseId, $amount);
        if ($warn) {
            $this->stockService->addLowStockWarning($proCode, $warehouseId);
        }
    }
}
