<?php

namespace App\Repositories\Sales\Quote;

use App\Enum\Inventary\ProType;
use App\Models\Product;
use App\Models\SaleOrder;
use Illuminate\Pagination\LengthAwarePaginator;

class QuoteRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return SaleOrder::with(['customer', 'transactionType'])
            ->where('sls_trans_type_code', 'COT')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findWithData(int $id): SaleOrder
    {
        return SaleOrder::with(['movements', 'customer', 'transactionType'])->findOrFail($id);
    }

    public function getAvailableProducts(): array
    {
        return Product::with(['category', 'brand', 'stocks'])
            ->where(function ($query) {
                $query->where('pro_type', ProType::COMPUESTO->value)
                    ->orWhereHas('stocks', function ($stock) {
                        $stock->where('stk_cant', '>', 0);
                    });
            })
            ->get()
            ->map(function (Product $product) {
                $latestPrice = $product->latestPrice();
                $latestCost = $product->latestCost();

                if ($product->pro_type->value === ProType::COMPUESTO->value) {
                    $hasData = $latestPrice && $latestCost;
                } else {
                    $hasData = $latestPrice && $latestCost
                        && (float) $latestPrice->price_usd > (float) $latestCost->cost_pro_cost_ref;
                }

                if (! $hasData) {
                    return null;
                }

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
                    'latest_price' => (float) $latestPrice->price_usd,
                    'latest_utility' => (float) $latestPrice->price_utility,
                    'total_stock' => (float) $product->stocks->sum('stk_cant'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function findFirstByNumber(string $number): ?SaleOrder
    {
        return SaleOrder::where('sls_number', $number)->first();
    }
}
