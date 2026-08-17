<?php

namespace App\Repositories\Inventory;

use App\Models\CostHistory;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Stock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryKpiRepository
{
    private const TTL = 60;

    public function stockByWarehouse(): array
    {
        return Cache::remember('dashboard:inventory:stock_by_warehouse', self::TTL, function () {
            return DB::table('warehouses as w')
                ->leftJoinSub(
                    Stock::query()
                        ->selectRaw('stk_war_id, SUM(stk_cant) as total_quantity, COUNT(*) as product_count')
                        ->groupBy('stk_war_id'),
                    's',
                    's.stk_war_id',
                    '=',
                    'w.id',
                )
                ->selectRaw('w.id as warehouse_id, w.war_name, COALESCE(s.total_quantity, 0) as total_quantity, COALESCE(s.product_count, 0) as product_count')
                ->orderBy('w.id')
                ->get()
                ->map(fn ($row) => [
                    'warehouse_id' => (int) $row->warehouse_id,
                    'war_name' => (string) $row->war_name,
                    'total_quantity' => (float) $row->total_quantity,
                    'product_count' => (int) $row->product_count,
                ])
                ->all();
        });
    }

    public function stockDetail(): array
    {
        return Cache::remember('dashboard:inventory:stock_detail', self::TTL, function () {
            return Stock::query()
                ->join('warehouses as w', 'w.id', '=', 'stocks.stk_war_id')
                ->join('products as p', 'p.pro_code', '=', 'stocks.stk_pro_code')
                ->selectRaw('stocks.stk_pro_code as pro_code, p.pro_name, p.pro_und, stocks.stk_war_id as war_id, w.war_name, stocks.stk_cant as quantity')
                ->orderBy('stocks.stk_war_id')
                ->orderBy('p.pro_name')
                ->get()
                ->map(fn ($row) => [
                    'pro_code' => (string) $row->pro_code,
                    'pro_name' => (string) $row->pro_name,
                    'pro_und' => (string) $row->pro_und,
                    'war_id' => (int) $row->war_id,
                    'war_name' => (string) $row->war_name,
                    'quantity' => (float) $row->quantity,
                ])
                ->all();
        });
    }

    public function inventoryValue(): array
    {
        return Cache::remember('dashboard:inventory:value', self::TTL, function () {
            $totalUsd = $this->totalUsdValue();

            $value = ['USD' => $totalUsd];

            $rate = Rate::query()->latest('rate_date')->first();

            if ($rate !== null) {
                $value['COP'] = Rate::convert($totalUsd, 'USD', 'COP', $rate);
                $value['BS'] = Rate::convert($totalUsd, 'USD', 'BS', $rate);
            }

            return $value;
        });
    }

    public function inventoryValueDetail(): array
    {
        return Cache::remember('dashboard:inventory:value_detail', self::TTL, function () {
            $codes = $this->stockTotals()->keys()->all();

            if ($codes === []) {
                return [];
            }

            $stocks = $this->stockTotals();
            $costs = $this->latestCosts($codes);
            $products = $this->productNames($codes);

            $rows = [];

            foreach ($stocks as $code => $stock) {
                $quantity = (float) $stock->total;
                $unitCostRef = $costs[$code] ?? 0;

                $rows[] = [
                    'pro_code' => $code,
                    'pro_name' => $products[$code]['name'] ?? $code,
                    'pro_und' => $products[$code]['und'] ?? '',
                    'total_quantity' => $quantity,
                    'unit_cost_ref' => $unitCostRef,
                    'total_value_ref' => round($quantity * $unitCostRef, 4),
                ];
            }

            usort($rows, fn (array $a, array $b) => $b['total_value_ref'] <=> $a['total_value_ref']);

            return $rows;
        });
    }

    private function totalUsdValue(): float
    {
        $stocks = $this->stockTotals();
        $costs = $this->latestCosts($stocks->keys()->all());

        $total = 0.0;

        foreach ($stocks as $code => $stock) {
            $total += (float) $stock->total * ($costs[$code] ?? 0);
        }

        return round($total, 4);
    }

    private function stockTotals(): Collection
    {
        return DB::table('stocks')
            ->selectRaw('stk_pro_code, SUM(stk_cant) as total')
            ->groupBy('stk_pro_code')
            ->get()
            ->keyBy('stk_pro_code');
    }

    private function latestCosts(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return CostHistory::query()
            ->whereIn('cost_pro_code', $codes)
            ->orderByDesc('cost_date')
            ->orderByDesc('id')
            ->get(['cost_pro_code', 'cost_pro_cost_ref'])
            ->unique('cost_pro_code')
            ->pluck('cost_pro_cost_ref', 'cost_pro_code')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function productNames(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        return Product::query()
            ->whereIn('pro_code', $codes)
            ->get(['pro_code', 'pro_name', 'pro_und'])
            ->mapWithKeys(function (Product $product) {
                return [$product->pro_code => [
                    'name' => $product->pro_name,
                    'und' => $product->pro_und?->value ?? '',
                ]];
            })
            ->all();
    }
}
