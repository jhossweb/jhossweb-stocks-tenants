<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Inventory\Warehouses\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        $warehouses = Warehouse::query()
            ->withCount('stocks')
            ->orderBy('war_name')
            ->get(['id', 'war_name']);

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        Warehouse::create($request->validated());

        return redirect()->back()->with('success', 'Almacén creado exitosamente.');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()->back()->with('success', 'Almacén actualizado exitosamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        if ($warehouse->isInUse()) {
            return redirect()->back()->withErrors([
                'error' => "No se puede eliminar el almacén '{$warehouse->war_name}' porque tiene stock o movimientos registrados.",
            ]);
        }

        $warehouse->delete();

        return redirect()->back()->with('success', 'Almacén eliminado exitosamente.');
    }
}
