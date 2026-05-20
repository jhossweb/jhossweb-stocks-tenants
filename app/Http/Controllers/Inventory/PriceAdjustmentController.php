<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PriceAdjustmentController extends Controller
{
    public function index()
    {
        $products = Product::all()->map(function ($product) {
            $latestCost = $product->latestCost();
            $latestPrice = $product->latestPrice();

            return [
                'pro_code' => $product->pro_code,
                'pro_name' => $product->pro_name,
                'last_cost' => $latestCost ? $latestCost->cost_pro_cost_ref : 0,
                'last_utility' => $latestPrice ? $latestPrice->price_utility : 0,
                'last_price' => $latestPrice ? $latestPrice->price_usd : 0,
            ];
        });

        return Inertia::render('Inventory/AdjustPrices', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'adjustments' => 'required|array',
            'adjustments.*.pro_code' => 'required|exists:products,pro_code',
            'adjustments.*.utility' => 'required|numeric',
            'adjustments.*.price' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['adjustments'] as $adj) {
                // Only save if price is greater than 0
                if ($adj['price'] > 0) {
                    PriceHistory::create([
                        'price_pro_code' => $adj['pro_code'],
                        'price_utility' => $adj['utility'],
                        'price_usd' => $adj['price'],
                        'price_date' => now()->format('Y-m-d'),
                        'price_user_id' => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('success', 'Precios ajustados correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al ajustar precios: '.$e->getMessage()]);
        }
    }
}
