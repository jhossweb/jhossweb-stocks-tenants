<?php

namespace App\Actions\Sales\Quote;

use App\Enum\Inventary\ProType;
use App\Models\Product;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\Warehouse;

class ValidateQuoteStockAction
{
    private const TOLERANCE = 1e-6;

    /**
     * Verifica que el inventario disponible cubra la conversión de la cotización.
     * Para productos compuestos, valida el stock de cada componente en el almacén del ítem.
     *
     * @return array{ok: bool, missing: array<int, array<string, mixed>>}
     */
    public function handle(SaleOrder $quote): array
    {
        $quote->load('movements.product.componentes');

        $required = [];
        $parents = [];

        foreach ($quote->movements as $movement) {
            $product = $movement->product;
            $warehouseId = (int) $movement->mvs_war_id;
            $quantity = (float) $movement->mvs_quantity;

            if (! $product) {
                continue;
            }

            if ($product->pro_type?->value === ProType::COMPUESTO->value) {
                foreach ($product->componentes as $componente) {
                    $componentQty = (float) $componente->pivot->cp_quantity * $quantity;
                    $key = $componente->pro_code.'|'.$warehouseId;
                    $required[$key] = ($required[$key] ?? 0) + $componentQty;
                    $parents[$componente->pro_code][$warehouseId][] = $product->pro_name;
                }
            } else {
                $key = $product->pro_code.'|'.$warehouseId;
                $required[$key] = ($required[$key] ?? 0) + $quantity;
            }
        }

        $missing = [];

        foreach ($required as $key => $requiredQty) {
            [$proCode, $warehouseId] = explode('|', $key);
            $warehouseId = (int) $warehouseId;

            $available = (float) Stock::where('stk_pro_code', $proCode)
                ->where('stk_war_id', $warehouseId)
                ->sum('stk_cant');

            if ($requiredQty - $available <= self::TOLERANCE) {
                continue;
            }

            $product = Product::where('pro_code', $proCode)->first();
            $warehouse = Warehouse::find($warehouseId);

            $missing[] = [
                'pro_code' => $proCode,
                'pro_name' => $product?->pro_name ?? $proCode,
                'pro_und' => $product?->pro_und?->value ?? 'und',
                'warehouse_id' => $warehouseId,
                'warehouse_name' => $warehouse?->war_name ?? "Almacén {$warehouseId}",
                'required' => $requiredQty,
                'available' => $available,
                'shortage' => $requiredQty - $available,
                'via_parent' => isset($parents[$proCode][$warehouseId])
                    ? array_values(array_unique($parents[$proCode][$warehouseId]))
                    : [],
            ];
        }

        return [
            'ok' => empty($missing),
            'missing' => $missing,
        ];
    }
}
