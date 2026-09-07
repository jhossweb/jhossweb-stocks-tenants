<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PriceHistory;
use App\Repositories\Inventory\ProductPriceRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PriceAdjustmentController extends Controller
{
    public function __construct(
        private readonly ProductPriceRepository $productPrices,
    ) {}

    public function index()
    {
        $products = $this->productPrices->adjustmentRows();

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
