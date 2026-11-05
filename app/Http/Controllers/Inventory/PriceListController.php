<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Reports\Inventory\PriceListReport;
use App\Repositories\Inventory\ProductPriceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PriceListController extends Controller
{
    public function __construct(
        private readonly ProductPriceRepository $productPrices,
        private readonly PriceListReport $report,
    ) {}

    /**
     * Lista de precios vigente con filtros server-side.
     */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'sin_precio' => ['nullable', 'boolean'],
            'bajo_costo' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,precio,fecha'],
        ]);

        return Inertia::render('Inventory/ListPrices/Index', [
            'rows' => $this->productPrices->paginatedPrices($filters),
            // (object) garantiza JSON {} aunque no haya filtros; un [] rompería
            // la resolución de propiedades en el cliente (Array.prototype.sort).
            'filters' => (object) collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->all(),
            'categories' => Category::active()->orderBy('cat_name')->get(['id', 'cat_name']),
            'brands' => Brand::active()->orderBy('bra_name')->get(['id', 'bra_name']),
        ]);
    }

    /**
     * PDF versión cliente (sin costo ni margen).
     */
    public function exportPdf(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        return $this->report->download($validated);
    }

    /**
     * Histórico de precios y costos para el drawer (AJAX).
     */
    public function history(string $proCode): JsonResponse
    {
        $product = Product::query()->where('pro_code', $proCode)->firstOrFail();

        return response()->json([
            'product' => [
                'pro_code' => $product->pro_code,
                'pro_name' => $product->pro_name,
                'pro_und' => $product->pro_und->value,
                'category' => $product->category?->cat_name,
                'brand' => $product->brand?->bra_name,
            ],
            'history' => $this->productPrices->history($proCode),
        ]);
    }
}
