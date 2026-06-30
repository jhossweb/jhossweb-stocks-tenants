<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryAdjustmentRequest;
use App\Models\AdjustmentReason;
use App\Models\Inventory;
use App\Models\MovementInventory;
use App\Models\Product;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class InventoryAdjustmentController extends Controller
{
    public function index()
    {
        $adjustments = Inventory::with(['movements.product', 'movements.warehouse', 'movements.reason', 'transactionType'])
            ->where('inv_trans_type_code', 'INV')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Inventory/Adjustments/Index', [
            'adjustments' => $adjustments,
        ]);
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $reasons = AdjustmentReason::all();

        $products = Product::with(['category', 'brand'])
            ->get()
            ->map(function ($product) {
                $stocks = $product->stocks()
                    ->where('stk_cant', '>', 0)
                    ->get();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und,
                    'pro_iva' => $product->pro_iva,
                    'pro_type' => $product->pro_type,
                    'stocks' => $stocks->map(function ($stock) {
                        return [
                            'war_id' => $stock->stk_war_id,
                            'cant' => (float) $stock->stk_cant,
                        ];
                    })->values(),
                    'total_stock' => (float) $stocks->sum('stk_cant'),
                ];
            })
            ->values();

        return Inertia::render('Inventory/Adjustments/Create', [
            'warehouses' => $warehouses,
            'products' => $products,
            'reasons' => $reasons,
            'transactionTypes' => TransactionType::all(),
        ]);
    }

    public function store(StoreInventoryAdjustmentRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'INV')->first();

            $inventory = new Inventory;
            $inventory->inv_number = $validated['inv_number'];
            $inventory->inv_date = $validated['inv_date'];
            $inventory->inv_trans_type_code = $transactionType->trans_type_code;
            $inventory->inv_notes = $validated['inv_notes'] ?? null;
            $inventory->inv_status = 'completed';
            $inventory->save();

            foreach ($validated['items'] as $item) {
                $movement = new MovementInventory;
                $movement->min_inv_number = $inventory->inv_number;
                $movement->min_trans_type_code = $inventory->inv_trans_type_code;
                $movement->min_factor = $transactionType->trans_type_factor;
                $movement->min_pro_code = $item['min_pro_code'];
                $movement->min_war_id = $item['min_war_id'];
                $movement->min_quantity = $item['min_quantity'];
                $movement->min_reason_id = $item['min_reason_id'];
                $movement->save();
            }

            DB::commit();

            return redirect()->route('inventory.adjustments.index')
                ->with('success', 'Ajuste de inventario guardado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar el ajuste: '.$e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $adjustment = Inventory::with(['movements.product', 'movements.warehouse', 'movements.reason', 'transactionType'])
            ->findOrFail($id);

        $warehouses = Warehouse::all();
        $reasons = AdjustmentReason::all();

        $products = Product::with(['category', 'brand'])
            ->get()
            ->map(function ($product) {
                $stocks = $product->stocks()
                    ->where('stk_cant', '>', 0)
                    ->get();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und,
                    'pro_iva' => $product->pro_iva,
                    'pro_type' => $product->pro_type,
                    'stocks' => $stocks->map(function ($stock) {
                        return [
                            'war_id' => $stock->stk_war_id,
                            'cant' => (float) $stock->stk_cant,
                        ];
                    })->values(),
                    'total_stock' => (float) $stocks->sum('stk_cant'),
                ];
            })
            ->values();

        return Inertia::render('Inventory/Adjustments/Edit', [
            'adjustment' => $adjustment,
            'warehouses' => $warehouses,
            'products' => $products,
            'reasons' => $reasons,
            'transactionTypes' => TransactionType::all(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $adjustment = Inventory::findOrFail($id);

        $validated = $request->validate([
            'inv_number' => 'required|string|max:50|unique:inventories,inv_number,'.$id,
            'inv_date' => 'required|date',
            'inv_notes' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:movements_inventories,id',
            'items.*.min_pro_code' => 'required|exists:products,pro_code',
            'items.*.min_war_id' => 'required|exists:warehouses,id',
            'items.*.min_quantity' => 'required|numeric|min:0.01',
            'items.*.min_reason_id' => 'required|exists:adjustment_reasons,id',
        ]);

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'INV')->first();

            $adjustment->inv_number = $validated['inv_number'];
            $adjustment->inv_date = $validated['inv_date'];
            $adjustment->inv_notes = $validated['inv_notes'] ?? null;
            $adjustment->save();

            $existingIds = $adjustment->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementInventory::find($item['id']);
                } else {
                    $movement = new MovementInventory;
                    $movement->min_inv_number = $adjustment->inv_number;
                    $movement->min_trans_type_code = $adjustment->inv_trans_type_code ?? $transactionType->trans_type_code;
                    $movement->min_factor = $transactionType->trans_type_factor;
                }

                $movement->min_pro_code = $item['min_pro_code'];
                $movement->min_war_id = $item['min_war_id'];
                $movement->min_quantity = $item['min_quantity'];
                $movement->min_reason_id = $item['min_reason_id'];
                $movement->save();

                $newIds[] = $movement->id;
            }

            $itemsToDelete = array_diff($existingIds, $newIds);
            if (! empty($itemsToDelete)) {
                MovementInventory::whereIn('id', $itemsToDelete)->delete();
            }

            DB::commit();

            return redirect()->route('inventory.adjustments.index')
                ->with('success', 'Ajuste de inventario actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar el ajuste: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $adjustment = Inventory::findOrFail($id);

        try {
            DB::beginTransaction();
            $adjustment->movements()->delete();
            $adjustment->delete();
            DB::commit();

            return redirect()->route('inventory.adjustments.index')
                ->with('success', 'Ajuste de inventario eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al eliminar el ajuste: '.$e->getMessage()]);
        }
    }
}
