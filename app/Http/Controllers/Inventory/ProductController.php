<?php

namespace App\Http\Controllers\Inventory;

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
        $products = Product::with(['category', 'brand'])->latest()->get();
        
        return Inertia::render('Inventory/Products/Index', [
            'products' => $products,
            'categories' => Category::all(['id', 'cat_name']),
            'brands' => Brand::all(['id', 'bra_name']),
            'undOptions' => UndProduct::values(),
            'ivaOptions' => array_combine(TypeIva::values(), array_map(function ($case) {
                return $case->name; // GN, RE, EX etc if needed
            }, TypeIva::cases())),
            'rawIvaOptions' => TypeIva::values()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());

        return redirect()->back()->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

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
