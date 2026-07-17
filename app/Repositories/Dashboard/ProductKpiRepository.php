<?php

namespace App\Repositories\Dashboard;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductKpiRepository
{
    public function totalSkus(): int
    {
        return Product::count();
    }

    public function lowStock(): array
    {
        return Product::query()
            ->select('pro_name', 'stk_cant', 'pro_cant_min')
            ->join('stocks', 'pro_code', '=', 'stk_pro_code')
            ->whereColumn('stk_cant', '<=', 'pro_cant_min')
            ->get()
            ->toArray();
    }

    public function topSelling(int $limit = 5, ?string $month = null): array
    {
        $month ??= now()->format('Y-m');

        return Product::query()
            ->select('products.pro_name', DB::raw('COALESCE(SUM(ms.mvs_quantity), 0) as quantity'))
            ->leftJoin('movements_sales as ms', 'products.pro_code', '=', 'ms.mvs_pro_code')
            ->whereRaw("strftime('%Y-%m', ms.created_at) = ?", [$month])
            ->groupBy('products.pro_code', 'products.pro_name')
            ->having('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function worstSelling(int $limit = 5, ?string $month = null): array
    {
        $month ??= now()->format('Y-m');

        return Product::query()
            ->select('products.pro_name', DB::raw('COALESCE(SUM(ms.mvs_quantity), 0) as quantity'))
            ->leftJoin('movements_sales as ms', 'products.pro_code', '=', 'ms.mvs_pro_code')
            ->whereRaw("strftime('%Y-%m', ms.created_at) = ?", [$month])
            ->groupBy('products.pro_code', 'products.pro_name')
            ->having('quantity', '>', 0)
            ->orderBy('quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function topSellingReport(?string $month, int $limit = 10): array
    {
        $month ??= now()->format('Y-m');

        return Product::query()
            ->select('products.pro_name', DB::raw('COALESCE(SUM(ms.mvs_quantity), 0) as quantity'))
            ->leftJoin('movements_sales as ms', 'products.pro_code', '=', 'ms.mvs_pro_code')
            ->whereRaw("strftime('%Y-%m', ms.created_at) = ?", [$month])
            ->groupBy('products.pro_code', 'products.pro_name')
            ->having('quantity', '>', 0)
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function worstSellingReport(?string $month, int $limit = 10): array
    {
        $month ??= now()->format('Y-m');

        return Product::query()
            ->select('products.pro_name', DB::raw('COALESCE(SUM(ms.mvs_quantity), 0) as quantity'))
            ->leftJoin('movements_sales as ms', 'products.pro_code', '=', 'ms.mvs_pro_code')
            ->whereRaw("strftime('%Y-%m', ms.created_at) = ?", [$month])
            ->groupBy('products.pro_code', 'products.pro_name')
            ->having('quantity', '>', 0)
            ->orderBy('quantity')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
