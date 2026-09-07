<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Brands\StoreBrandRequest;
use App\Http\Requests\Inventory\Brands\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('bra_name')
            ->get(['id', 'bra_name']);

        return Inertia::render('Inventory/Brands/Index', [
            'brands' => $brands,
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        return redirect()->back()->with('success', 'Marca creada exitosamente.');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        return redirect()->back()->with('success', 'Marca actualizada exitosamente.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return redirect()->back()->withErrors([
                'error' => "No se puede eliminar la marca '{$brand->bra_name}' porque tiene productos asociados.",
            ]);
        }

        $brand->delete();

        return redirect()->back()->with('success', 'Marca eliminada exitosamente.');
    }
}
