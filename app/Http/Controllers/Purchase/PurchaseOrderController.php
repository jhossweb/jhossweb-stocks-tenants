<?php

namespace App\Http\Controllers\Purchase;

use App\Enum\Purchases\StatusPurchases;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Models\MovementsPurchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchases = PurchaseOrder::with(['supplier', 'transactionType'])
            ->where('invoice_trans_type_code', 'FAC')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
        ]);
    }

    public function create()
    {
        // To build the form
        return Inertia::render('Purchases/Create', [
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'warehouses' => Warehouse::all(),
            'transactionTypes' => TransactionType::all(), // Maybe filter by those related to purchases
            'rates' => Rate::all()->map(function ($rate) {
                return [
                    'rate_date' => optional($rate->rate_date)->format('Y-m-d'),
                    'rate_usd' => $rate->rate_usd,
                    'rate_cop' => $rate->rate_cop,
                    'rate_eur' => $rate->rate_eur,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:purchase_orders,invoice_number',
            'invoice_date' => 'required|date',
            'invoice_supplier_id' => 'required|exists:suppliers,id',
            'invoice_valorcam' => 'required|numeric',
            'invoice_coin' => 'required|string',
            'invoice_subtotal' => 'required|numeric',
            'invoice_exento' => 'required|numeric',
            'invoice_iva' => 'required|numeric',
            'invoice_total' => 'required|numeric',

            'items' => 'required|array|min:1',
            'items.*.mov_pro_code' => 'required|exists:products,pro_code',
            'items.*.mov_war_id' => 'required|exists:warehouses,id',
            'items.*.mov_quantity' => 'required|numeric|min:0.01',
            'items.*.mov_unit_cost' => 'required|numeric|min:0',
            'items.*.mov_unit_cost_ref' => 'required|numeric|min:0',
            'items.*.mov_pro_iva' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Obtener automáticamente el tipo de transacción 'Compra'
            $transactionType = TransactionType::where('trans_type_code', 'FAC')->first();

            $purchase = new PurchaseOrder;
            $purchase->invoice_number = $validated['invoice_number'];
            $purchase->invoice_date = $validated['invoice_date'];
            $purchase->invoice_trans_type_code = $transactionType->trans_type_code;
            $purchase->invoice_supplier_id = $validated['invoice_supplier_id'];
            $purchase->invoice_valorcam = $validated['invoice_valorcam'];
            $purchase->invoice_coin = $validated['invoice_coin'];
            $purchase->invoice_subtotal = $validated['invoice_subtotal'];
            $purchase->invoice_exento = $validated['invoice_exento'];
            $purchase->invoice_iva = $validated['invoice_iva'];
            $purchase->invoice_total = $validated['invoice_total'];
            $purchase->invoice_status = StatusPurchases::PENDING->value;
            $purchase->save();

            // Guardar detalles
            foreach ($validated['items'] as $item) {
                $movement = new MovementsPurchase;
                $movement->mov_invoice_number = $purchase->invoice_number;
                $movement->mov_invoice_trans_type_code = $purchase->invoice_trans_type_code;
                $movement->mov_factor = $transactionType->trans_type_factor; // Factor real desde transaction_types
                $movement->mov_pro_code = $item['mov_pro_code'];
                $movement->mov_pro_iva = $item['mov_pro_iva'];
                $movement->mov_war_id = $item['mov_war_id'];
                $movement->mov_quantity = $item['mov_quantity'];
                $movement->mov_unit_cost = $item['mov_unit_cost'];
                $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
                $movement->save(); // This will trigger the observer MovPurchasesObserver!
            }

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Compra guardada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar la compra: '.$e->getMessage()]);
        }
    }

    public function show(PurchaseOrder $purchase)
    {
        return redirect()->route('purchases.edit', $purchase->id);
    }

    public function edit($id)
    {
        $purchase = PurchaseOrder::with(['movements', 'supplier', 'transactionType'])->findOrFail($id);

        return Inertia::render('Purchases/Edit', [
            'purchase' => $purchase,
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'warehouses' => Warehouse::all(),
            'transactionTypes' => TransactionType::all(),
            'rates' => Rate::all()->map(function ($rate) {
                return [
                    'rate_date' => optional($rate->rate_date)->format('Y-m-d'),
                    'rate_usd' => $rate->rate_usd,
                    'rate_cop' => $rate->rate_cop,
                    'rate_eur' => $rate->rate_eur,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $purchase = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:purchase_orders,invoice_number,'.$purchase->id,
            'invoice_date' => 'required|date',
            'invoice_supplier_id' => 'required|exists:suppliers,id',
            'invoice_valorcam' => 'required|numeric',
            'invoice_coin' => 'required|string',
            'invoice_subtotal' => 'required|numeric',
            'invoice_exento' => 'required|numeric',
            'invoice_iva' => 'required|numeric',
            'invoice_total' => 'required|numeric',

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:movements_purchases,id',
            'items.*.mov_pro_code' => 'required|exists:products,pro_code',
            'items.*.mov_war_id' => 'required|exists:warehouses,id',
            'items.*.mov_quantity' => 'required|numeric|min:0.01',
            'items.*.mov_unit_cost' => 'required|numeric|min:0',
            'items.*.mov_unit_cost_ref' => 'required|numeric|min:0',
            'items.*.mov_pro_iva' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'FAC')->first();

            $purchase->invoice_number = $validated['invoice_number'];
            $purchase->invoice_date = $validated['invoice_date'];
            $purchase->invoice_supplier_id = $validated['invoice_supplier_id'];
            $purchase->invoice_valorcam = $validated['invoice_valorcam'];
            $purchase->invoice_coin = $validated['invoice_coin'];
            $purchase->invoice_subtotal = $validated['invoice_subtotal'];
            $purchase->invoice_exento = $validated['invoice_exento'];
            $purchase->invoice_iva = $validated['invoice_iva'];
            $purchase->invoice_total = $validated['invoice_total'];
            $purchase->save();

            $existingIds = $purchase->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementsPurchase::find($item['id']);
                } else {
                    $movement = new MovementsPurchase;
                    $movement->mov_invoice_number = $purchase->invoice_number;
                    $movement->mov_invoice_trans_type_code = $purchase->invoice_trans_type_code ?? $transactionType->trans_type_code;
                    $movement->mov_factor = $transactionType->trans_type_factor;
                }

                $movement->mov_pro_code = $item['mov_pro_code'];
                $movement->mov_pro_iva = $item['mov_pro_iva'];
                $movement->mov_war_id = $item['mov_war_id'];
                $movement->mov_quantity = $item['mov_quantity'];
                $movement->mov_unit_cost = $item['mov_unit_cost'];
                $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
                $movement->save();

                $newIds[] = $movement->id;
            }

            // Excluir los que se borraron
            $itemsToDelete = array_diff($existingIds, $newIds);
            if (! empty($itemsToDelete)) {
                foreach ($itemsToDelete as $delId) {
                    if ($model = MovementsPurchase::find($delId)) {
                        $model->delete();
                    }
                }
            }

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Compra actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la compra: '.$e->getMessage()]);
        }
    }
}
