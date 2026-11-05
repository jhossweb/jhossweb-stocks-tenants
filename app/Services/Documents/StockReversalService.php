<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockReversalService
{
    public function __construct(
        private readonly StockService $stock,
    ) {}

    public function reverse(Model $document): void
    {
        DB::transaction(fn () => $this->process($document, true));
    }

    public function assertCanReverse(Model $document): void
    {
        $this->process($document, false);
    }

    private function process(Model $document, bool $apply): void
    {
        if ($document instanceof SaleOrder) {
            $this->reverseSales($document, $apply);

            return;
        }

        if ($document instanceof PurchaseOrder) {
            $this->reversePurchases($document, $apply);

            return;
        }

        if ($document instanceof Inventory) {
            $this->reverseInventory($document, $apply);
        }
    }

    private function reverseSales(SaleOrder $sale, bool $apply): void
    {
        foreach ($sale->movements()->get() as $movement) {
            $type = strtolower((string) $movement->mvs_trans_type_code);

            if (! in_array($type, ['facv', 'devv'], true)) {
                continue;
            }

            $amount = (float) $movement->mvs_quantity * (int) $movement->mvs_factor;

            $this->reverseComponentAware(
                (string) $movement->mvs_pro_code,
                (int) $movement->mvs_war_id,
                $amount,
                $apply,
            );
        }
    }

    private function reversePurchases(PurchaseOrder $purchase, bool $apply): void
    {
        foreach ($purchase->movements()->get() as $movement) {
            $type = strtolower((string) $movement->mov_invoice_trans_type_code);

            if (! in_array($type, ['fac', 'devc'], true)) {
                continue;
            }

            $factor = $this->stock->getConversionFactor((string) $movement->mov_pro_code);
            $amount = (float) $movement->mov_quantity * (int) $movement->mov_factor * $factor;

            $this->applyDelta((string) $movement->mov_pro_code, (int) $movement->mov_war_id, -$amount, $apply);
        }
    }

    private function reverseInventory(Inventory $inventory, bool $apply): void
    {
        foreach ($inventory->movements()->get() as $movement) {
            if (strtolower((string) $movement->min_trans_type_code) !== 'inv') {
                continue;
            }

            $amount = (float) $movement->min_quantity * (int) $movement->min_factor;

            $this->reverseComponentAware(
                (string) $movement->min_pro_code,
                (int) $movement->min_war_id,
                $amount,
                $apply,
            );
        }
    }

    private function reverseComponentAware(string $proCode, int $warehouseId, float $originalAmount, bool $apply): void
    {
        $product = Product::with('componentes')->where('pro_code', $proCode)->first();

        if ($product === null) {
            return;
        }

        if ($product->pro_type?->value === 'compuesto') {
            foreach ($product->componentes as $component) {
                $componentAmount = (float) $component->pivot->cp_quantity * $originalAmount;
                $this->applyDelta($component->pro_code, $warehouseId, -$componentAmount, $apply);
            }

            return;
        }

        $this->applyDelta($proCode, $warehouseId, -$originalAmount, $apply);
    }

    private function applyDelta(string $proCode, int $warehouseId, float $delta, bool $apply): void
    {
        if (abs($delta) < 0.0000001) {
            return;
        }

        if ($delta < 0) {
            $this->assertStockAvailable($proCode, $warehouseId, abs($delta));
        }

        if ($apply) {
            $this->stock->updateStock($proCode, $warehouseId, $delta);
        }
    }

    private function assertStockAvailable(string $proCode, int $warehouseId, float $required): void
    {
        $available = (float) (DB::table('stocks')
            ->where('stk_pro_code', $proCode)
            ->where('stk_war_id', $warehouseId)
            ->value('stk_cant') ?? 0);

        if ($available >= $required) {
            return;
        }

        $productName = Product::where('pro_code', $proCode)->value('pro_name') ?? $proCode;
        $warehouseName = Warehouse::whereKey($warehouseId)->value('war_name') ?? "almacén {$warehouseId}";

        throw new \DomainException(
            "No hay existencias suficientes de {$productName} en {$warehouseName}: se necesitan "
            .number_format($required, 2, '.', ',')
            .' y hay '.number_format($available, 2, '.', ',').'.'
        );
    }
}
