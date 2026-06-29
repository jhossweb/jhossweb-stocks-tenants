<?php

namespace App\Http\Controllers\Inventory;

use App\Enum\Inventary\ProType;
use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Products\StoreProductRequest;
use App\Http\Requests\Inventory\Products\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with(['category', 'brand', 'componentes.category', 'componentes.brand'])
            ->withSum('stocks', 'stk_cant')
            ->latest()
            ->get();

        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => Category::all(['id', 'cat_name']),
            'brands' => Brand::all(['id', 'bra_name']),
            'undOptions' => UndProduct::values(),
            'ivaOptions' => array_combine(TypeIva::values(), array_map(function ($case) {
                return $case->name;
            }, TypeIva::cases())),
            'rawIvaOptions' => TypeIva::values(),
            'typeOptions' => ProType::values(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $components = $data['components'] ?? [];
        unset($data['components']);

        $product = Product::create($data);

        if ($product->pro_type->value === ProType::COMPUESTO->value && ! empty($components)) {
            $syncData = [];
            foreach ($components as $comp) {
                $syncData[$comp['cp_comp_pro_code']] = [
                    'cp_pro_und' => $comp['cp_pro_und'],
                    'cp_quantity' => $comp['cp_quantity'],
                ];
            }
            $product->componentes()->sync($syncData);
            $product->guardarCostoCompuesto();
        }

        return redirect()->back()->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validated();
        $components = $data['components'] ?? [];
        unset($data['components']);

        $product->update($data);

        if ($product->pro_type->value === ProType::COMPUESTO->value && ! empty($components)) {
            $syncData = [];
            foreach ($components as $comp) {
                $syncData[$comp['cp_comp_pro_code']] = [
                    'cp_pro_und' => $comp['cp_pro_und'],
                    'cp_quantity' => $comp['cp_quantity'],
                ];
            }
            $product->componentes()->sync($syncData);
            $product->guardarCostoCompuesto();
        } else {
            $product->componentes()->sync([]);
        }

        return redirect()->back()->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Producto eliminado exitosamente.');
    }
}
