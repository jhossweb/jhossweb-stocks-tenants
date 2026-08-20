<?php

namespace App\Services\Inventory;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockService
{
    protected array $lowStockWarnings = [];

    public function updateStock(string $proCode, int $warehouseId, float $amount): void
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

    /**
     * @return array{pro_name: string, stk_cant: float, pro_und: string}|null
     */
    public function checkMinStock(string $proCode, int $warehouseId): ?array
    {
        $stock = DB::table('stocks')
            ->where('stk_pro_code', $proCode)
            ->where('stk_war_id', $warehouseId)
            ->first();

        if (! $stock) {
            return null;
        }

        $product = Product::where('pro_code', $proCode)->first();

        if (! $product || $product->pro_cant_min <= 0) {
            return null;
        }

        if ($stock->stk_cant > $product->pro_cant_min) {
            return null;
        }

        return [
            'pro_name' => $product->pro_name,
            'stk_cant' => (float) $stock->stk_cant,
            'pro_und' => $product->pro_und->value,
        ];
    }

    public function addLowStockWarning(string $proCode, int $warehouseId): void
    {
        $warning = $this->checkMinStock($proCode, $warehouseId);

        if ($warning) {
            $this->lowStockWarnings[] = $warning;
        }
    }

    public function lowStockWarnings(): array
    {
        return $this->lowStockWarnings;
    }

    public function getConversionFactor(string $proCode): float
    {
        $product = Product::where('pro_code', $proCode)->first();

        if ($product && in_array($product->pro_und->value, ['mts', 'kg', 'm2'])) {
            return (float) ($product->pro_conversion_factor ?? 1);
        }

        return 1;
    }
}
