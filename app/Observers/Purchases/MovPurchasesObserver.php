<?php

namespace App\Observers\Purchases;

use App\Models\MovementsPurchase;
use App\Models\Product;
use App\Services\Dashboard\DashboardCacheInvalidator;
use DB;

class MovPurchasesObserver
{
    protected function getConversionFactor(string $proCode): float
    {
        $product = Product::where('pro_code', $proCode)->first();
        if ($product && in_array($product->pro_und->value, ['mts', 'kg', 'm2'])) {
            return (float) ($product->pro_conversion_factor ?? 1);
        }

        return 1;
    }

    public function created(MovementsPurchase $movement): void
    {
        DashboardCacheInvalidator::products();

        $transType = strtolower((string) $movement->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            $factor = $this->getConversionFactor($movement->mov_pro_code);
            $cantidadFinal = (float) $movement->mov_quantity * (int) $movement->mov_factor * $factor;

            $this->updateStock(
                $movement->mov_pro_code,
                $movement->mov_war_id,
                $cantidadFinal
            );
        }
    }

    public function updated(MovementsPurchase $movementsPurchase): void
    {
        DashboardCacheInvalidator::products();

        $oldCant = (float) $movementsPurchase->getOriginal('mov_quantity');
        $oldFactor = (int) $movementsPurchase->getOriginal('mov_factor');
        $oldWareId = $movementsPurchase->getOriginal('mov_war_id');
        $oldTransType = strtolower((string) $movementsPurchase->getOriginal('mov_invoice_trans_type_code'));
        $proCode = $movementsPurchase->mov_pro_code;
        $conversion = $this->getConversionFactor($proCode);

        if (in_array($oldTransType, ['fac', 'devc'])) {
            $this->updateStock($proCode, $oldWareId, -(($oldCant * $oldFactor) * $conversion));
        }

        $newTransType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);
        if (in_array($newTransType, ['fac', 'devc'])) {
            $this->updateStock(
                $proCode,
                $movementsPurchase->mov_war_id,
                ($movementsPurchase->mov_quantity * $movementsPurchase->mov_factor) * $conversion
            );
        }
    }

    public function deleted(MovementsPurchase $movementsPurchase): void
    {
        DashboardCacheInvalidator::products();

        $transType = strtolower((string) $movementsPurchase->mov_invoice_trans_type_code);

        if (in_array($transType, ['fac', 'devc'])) {
            $factor = $this->getConversionFactor($movementsPurchase->mov_pro_code);
            $this->updateStock(
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
