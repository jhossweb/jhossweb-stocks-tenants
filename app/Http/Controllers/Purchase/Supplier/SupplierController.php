<?php

namespace App\Http\Controllers\Purchase\Supplier;

use App\DTOs\Purchases\Supplier\SupplierDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\Supplier\StoreSupplierRequest;
use App\Http\Requests\Purchases\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\Purchases\Supplier\SupplierService;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierService $supplierService,
    ) {}

    public function index()
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => $this->supplierService->list(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->supplierService->create(SupplierDTO::fromArray($request->validated()));

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado exitosamente.');
    }

    public function show(Supplier $supplier)
    {
        return redirect()->route('suppliers.edit', $supplier->id);
    }

    public function edit(Supplier $supplier)
    {
        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $this->supplierService->update($supplier, SupplierDTO::fromArray($request->validated()));

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $this->supplierService->delete($supplier);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
