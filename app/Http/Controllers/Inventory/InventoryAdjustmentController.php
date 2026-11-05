<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryAdjustmentRequest;
use App\Http\Requests\Inventory\UpdateInventoryAdjustmentRequest;
use App\Models\Warehouse;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use App\Services\Inventory\InventoryAdjustmentService;
use Inertia\Inertia;
use Throwable;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryAdjustmentRepository $repository,
        protected InventoryAdjustmentService $service,
    ) {}

    public function index()
    {
        return Inertia::render('Inventory/Adjustments/Index', [
            'adjustments' => $this->repository->paginate(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Adjustments/Create', $this->formProps());
    }

    public function store(StoreInventoryAdjustmentRequest $request)
    {
        try {
            $this->service->create($request->toDto());
        } catch (Throwable $e) {
            return back()->withErrors([
                'error' => 'Ocurrió un error al guardar el ajuste: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('inventory.adjustments.index')
            ->with('success', 'Ajuste de inventario guardado exitosamente.');
    }

    public function edit(int $id)
    {
        return Inertia::render('Inventory/Adjustments/Edit', [
            'adjustment' => $this->repository->findOrFail($id),
            ...$this->formProps(),
        ]);
    }

    public function update(UpdateInventoryAdjustmentRequest $request, int $id)
    {
        $adjustment = $this->repository->findOrFail($id);

        try {
            $this->service->update($adjustment, $request->toDto());
        } catch (Throwable $e) {
            return back()->withErrors([
                'error' => 'Ocurrió un error al actualizar el ajuste: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('inventory.adjustments.index')
            ->with('success', 'Ajuste de inventario actualizado exitosamente.');
    }

    public function destroy(int $id)
    {
        $adjustment = $this->repository->findOrFail($id);

        try {
            $this->service->delete($adjustment);
        } catch (Throwable $e) {
            return back()->withErrors([
                'error' => 'Ocurrió un error al eliminar el ajuste: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('inventory.adjustments.index')
            ->with('success', 'Ajuste de inventario eliminado exitosamente.');
    }

    /**
     * Props compartidas entre create y edit. `reasons` va cacheado y por
     * tenant: es catálogo maestro y se consulta en cada visita al formulario.
     *
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'warehouses' => Warehouse::active()->get(),
            'products' => $this->repository->formProducts(),
            'reasons' => $this->repository->reasons(),
        ];
    }
}
