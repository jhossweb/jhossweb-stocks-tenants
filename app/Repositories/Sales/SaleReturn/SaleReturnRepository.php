<?php

namespace App\Repositories\Sales\SaleReturn;

use App\Enum\Inventary\ProType;
use App\Models\MovementsSale;
use App\Models\Product;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Stock;
use Illuminate\Support\Collection;

class SaleReturnRepository
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAvailableProducts(): Collection
    {
        $stockByProduct = Stock::where('stk_war_id', 1)
            ->where('stk_cant', '>', 0)
            ->pluck('stk_cant', 'stk_pro_code');

        return Product::with(['category', 'brand'])
            ->where(function ($query) use ($stockByProduct) {
                $query->whereIn('pro_code', $stockByProduct->keys())
                    ->orWhere('pro_type', ProType::COMPUESTO->value);
            })
            ->get()
            ->filter(function ($product) {
                $latestPrice = $product->latestPrice();
                $latestCost = $product->latestCost();

                if ($product->pro_type->value === ProType::COMPUESTO->value) {
                    return $latestPrice && $latestCost;
                }

                return $latestPrice && $latestCost
                    && $latestPrice->price_usd > $latestCost->cost_pro_cost_ref;
            })
            ->map(function ($product) use ($stockByProduct) {
                $latestPrice = $product->latestPrice();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und,
                    'pro_conversion_factor' => $product->pro_conversion_factor ?? 1,
                    'pro_cant_min' => $product->pro_cant_min ?? 0,
                    'pro_iva' => $product->pro_iva,
                    'pro_cat_id' => $product->pro_cat_id,
                    'pro_bra_id' => $product->pro_bra_id,
                    'pro_type' => $product->pro_type,
                    'category' => $product->category,
                    'brand' => $product->brand,
                    'latest_price' => $latestPrice->price_usd,
                    'latest_utility' => $latestPrice->price_utility,
                    'total_stock' => (float) $stockByProduct->get($product->pro_code, 0),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRatesForSelect(): Collection
    {
        return Rate::all()->map(function ($rate) {
            return [
                'rate_date' => optional($rate->rate_date)->format('Y-m-d'),
                'rate_usd' => $rate->rate_usd,
                'rate_cop' => $rate->rate_cop,
                'rate_eur' => $rate->rate_eur,
                'rate_cop_bs' => $rate->rate_cop_bs,
            ];
        });
    }

    public function getLatestRate(): ?Rate
    {
        return Rate::orderBy('rate_date', 'desc')->first();
    }

    public function findUnpaidSalesByCustomer(int $customerId): Collection
    {
        $sales = SaleOrder::with(['movements'])
            ->where('sls_customer_id', $customerId)
            ->where('sls_trans_type_code', 'FACV')
            ->get()
            ->filter(function ($sale) {
                foreach ($sale->movements as $movement) {
                    $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($sale) {
                        $q->where('sls_reference', $sale->sls_number);
                    })
                        ->where('mvs_pro_code', $movement->mvs_pro_code)
                        ->sum('mvs_quantity');

                    if ($movement->mvs_quantity > $returnedQty) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        return $sales->map(function ($sale) {
            return [
                'id' => $sale->id,
                'sls_number' => $sale->sls_number,
                'sls_date' => $sale->sls_date,
                'sls_total' => $sale->sls_total,
                'sls_coin' => $sale->sls_coin,
                'sls_valorcam' => $sale->sls_valorcam,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getSaleOrderItems(SaleOrder $saleOrder, ?int $excludeReturnId): Collection
    {
        $items = $saleOrder->movements()
            ->with(['product', 'warehouse'])
            ->get()
            ->map(function ($movement) use ($saleOrder, $excludeReturnId) {
                $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($saleOrder, $excludeReturnId) {
                    $q->where('sls_reference', $saleOrder->sls_number);
                    if ($excludeReturnId) {
                        $q->where('id', '!=', $excludeReturnId);
                    }
                })
                    ->where('mvs_pro_code', $movement->mvs_pro_code)
                    ->sum('mvs_quantity');

                return [
                    'mvs_pro_code' => $movement->mvs_pro_code,
                    'pro_name' => $movement->product?->pro_name,
                    'pro_iva' => $movement->mvs_pro_iva,
                    'mvs_war_id' => $movement->mvs_war_id,
                    'war_name' => $movement->warehouse?->war_name ?? $movement->warehouse?->id,
                    'mvs_quantity' => $movement->mvs_quantity,
                    'mvs_unit_price' => $movement->mvs_unit_price,
                    'mvs_unit_price_ref' => $movement->mvs_unit_price_ref,
                    'available_qty' => $movement->mvs_quantity - $returnedQty,
                ];
            });

        return $items->filter(function ($item) {
            return $item['available_qty'] > 0;
        })
            ->values();
    }
}
