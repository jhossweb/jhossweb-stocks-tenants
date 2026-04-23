<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\MovementsPurchase;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Enum\Purchases\StatusPurchases;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseReturnController extends Controller
{
    public function index()
    {
        $returns = PurchaseOrder::with(['supplier', 'transactionType'])
            ->where('invoice_trans_type_code', 'DEVC')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('PurchaseReturns/Index', [
            'returns' => $returns,
        ]);
    }

    public function create()
    {
        return Inertia::render('PurchaseReturns/Create', [
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'warehouses' => Warehouse::all(),
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
            'invoice_reference' => 'required|string|exists:purchase_orders,invoice_number',


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

            $transactionType = TransactionType::query()
                ->where('trans_type_code', 'DEVC')->first();

            if (!$transactionType) {
                throw new \RuntimeException('No existe un tipo de transacción de devolución configurado.');
            }

            // Validar que las cantidades a devolver no superen lo disponible (compras originales - devoluciones previas)
            $originalPurchase = PurchaseOrder::where('invoice_number', $validated['invoice_reference'])->first();
            if (!$originalPurchase) {
                throw new \RuntimeException('Factura origen no encontrada.');
            }
            $returnedMovements = $originalPurchase->returnedMovements;

            foreach ($validated['items'] as $item) {
                $originalQty = $originalPurchase->movements()
                    ->where('mov_pro_code', $item['mov_pro_code'])
                    ->where('mov_war_id', $item['mov_war_id'])
                    ->sum('mov_quantity');

                $alreadyReturned = $returnedMovements
                    ->where('mov_pro_code', $item['mov_pro_code'])
                    ->where('mov_war_id', $item['mov_war_id'])
                    ->sum('mov_quantity');

                // Usar round() para evitar problemas de coma flotante si ocurren decimales
                if (round($item['mov_quantity'], 4) > round($originalQty - $alreadyReturned, 4)) {
                    throw new \RuntimeException("La cantidad de devolución para el producto con código {$item['mov_pro_code']} supera el máximo permitido disponible.");
                }
            }

            $purchaseReturn = new PurchaseOrder();
            $purchaseReturn->invoice_number = $validated['invoice_number'];
            $purchaseReturn->invoice_date = $validated['invoice_date'];
            $purchaseReturn->invoice_trans_type_code = $transactionType->trans_type_code;
            $purchaseReturn->invoice_supplier_id = $validated['invoice_supplier_id'];
            $purchaseReturn->invoice_valorcam = $validated['invoice_valorcam'];
            $purchaseReturn->invoice_coin = $validated['invoice_coin'];
            $purchaseReturn->invoice_subtotal = $validated['invoice_subtotal'];
            $purchaseReturn->invoice_exento = $validated['invoice_exento'];
            $purchaseReturn->invoice_iva = $validated['invoice_iva'];
            $purchaseReturn->invoice_total = $validated['invoice_total'];
            $purchaseReturn->invoice_reference = $validated['invoice_reference'];
            $purchaseReturn->invoice_status = StatusPurchases::PENDING->value;
            $purchaseReturn->save();

            foreach ($validated['items'] as $item) {
                $movement = new MovementsPurchase();
                $movement->mov_invoice_number = $purchaseReturn->invoice_number;
                $movement->mov_invoice_trans_type_code = $purchaseReturn->invoice_trans_type_code;
                $movement->mov_factor = $transactionType->trans_type_factor;
                $movement->mov_pro_code = $item['mov_pro_code'];
                $movement->mov_pro_iva = $item['mov_pro_iva'];
                $movement->mov_war_id = $item['mov_war_id'];
                $movement->mov_quantity = $item['mov_quantity'];
                $movement->mov_unit_cost = $item['mov_unit_cost'];
                $movement->mov_unit_cost_ref = $item['mov_unit_cost_ref'];
                $movement->save();
            }

            DB::commit();

            return redirect()->route('purchase-returns.index')->with('success', 'Devolución de compra guardada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ocurrió un error al guardar la devolución: ' . $e->getMessage()]);
        }
    }

    public function show(PurchaseOrder $purchaseReturn)
    {
        return redirect()->route('purchase-returns.edit', $purchaseReturn->id);
    }

    public function edit($id)
    {
        $purchaseReturn = PurchaseOrder::with(['movements.product', 'movements.warehouse', 'supplier', 'transactionType'])->findOrFail($id);

        return Inertia::render('PurchaseReturns/Edit', [
            'purchaseReturn' => $purchaseReturn,
            'suppliers' => Supplier::all(),
            'products' => Product::all(),
            'warehouses' => Warehouse::all(),
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
        $purchaseReturn = PurchaseOrder::findOrFail($id);

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50|unique:purchase_orders,invoice_number,' . $purchaseReturn->id,
            'invoice_date' => 'required|date',
            'invoice_supplier_id' => 'required|exists:suppliers,id',
            'invoice_valorcam' => 'required|numeric',
            'invoice_coin' => 'required|string',
            'invoice_subtotal' => 'required|numeric',
            'invoice_exento' => 'required|numeric',
            'invoice_iva' => 'required|numeric',
            'invoice_total' => 'required|numeric',
            'invoice_reference' => 'required|string|exists:purchase_orders,invoice_number',

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

            $transactionType = TransactionType::where('trans_type_code', 'DEVC')->first();

            $purchaseReturn->invoice_number = $validated['invoice_number'];
            $purchaseReturn->invoice_date = $validated['invoice_date'];
            $purchaseReturn->invoice_supplier_id = $validated['invoice_supplier_id'];
            $purchaseReturn->invoice_valorcam = $validated['invoice_valorcam'];
            $purchaseReturn->invoice_coin = $validated['invoice_coin'];
            $purchaseReturn->invoice_subtotal = $validated['invoice_subtotal'];
            $purchaseReturn->invoice_exento = $validated['invoice_exento'];
            $purchaseReturn->invoice_iva = $validated['invoice_iva'];
            $purchaseReturn->invoice_total = $validated['invoice_total'];
            $purchaseReturn->invoice_reference = $validated['invoice_reference'];
            $purchaseReturn->save();

            $existingIds = $purchaseReturn->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementsPurchase::find($item['id']);
                } else {
                    $movement = new MovementsPurchase();
                    $movement->mov_invoice_number = $purchaseReturn->invoice_number;
                    $movement->mov_invoice_trans_type_code = $purchaseReturn->invoice_trans_type_code;
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

            $itemsToDelete = array_diff($existingIds, $newIds);
            if (!empty($itemsToDelete)) {
                foreach ($itemsToDelete as $delId) {
                    if ($model = MovementsPurchase::find($delId)) {
                        $model->delete();
                    }
                }
            }

            DB::commit();

            return redirect()->route('purchase-returns.index')->with('success', 'Devolución de compra actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la devolución: ' . $e->getMessage()]);
        }
    }

    public function getUnpaidInvoices(Supplier $supplier, Request $request)
    {
        $currentInvoiceId = $request->query('current_invoice_id');

        $invoices = PurchaseOrder::where('invoice_supplier_id', $supplier->id)
            ->whereHas('transactionType', fn($q) => $q->where('trans_type_factor', 1))
            ->withSum("movements", "mov_quantity")
            ->withSum('returnedMovements', 'mov_quantity') // total devuelto
            ->get()
            ->filter(function ($invoice) use ($currentInvoiceId) {
                // Si es la factura que estamos editando actualmente, la dejamos pasar siempre
                if ($currentInvoiceId && $invoice->id == $currentInvoiceId) {
                    return true;
                }

                $totalMovs = (float) $invoice->movements_sum_mov_quantity;
                $totalRet = (float) ($invoice->returned_movements_sum_mov_quantity ?? 0);
                return $totalRet < $totalMovs;
            })
            ->values();

        return response()->json($invoices);
    }

    public function getPurchaseOrderByNumber($invoiceNumber)
    {
        $purchase = PurchaseOrder::where('invoice_number', $invoiceNumber)->firstOrFail();
        return response()->json($purchase);
    }

    public function getPurchaseOrderItems(PurchaseOrder $purchaseOrder, Request $request)
    {
        $excludeReturnId = $request->query('exclude_return_id');
        $items = $purchaseOrder->movements()->with(['product', 'warehouse'])->get();

        // Obtener todos los movimientos que han sido devueltos a partir de esta factura
        // Si estamos editando, queremos excluir los movimientos de LA DEVOLUCIÓN ACTUAL de la sumatoria
        // para saber el verdadero 'disponible'.
        $returnedMovementsQuery = $purchaseOrder->returnedMovements();

        if ($excludeReturnId) {
            // returnedMovements es una relación hasManyThrough. 
            // Necesitamos asegurarnos de filtrar por el ID de la tabla purchase_orders intermedia
            $returnedMovementsQuery->where('purchase_orders.id', '!=', $excludeReturnId);
        }

        $returnedMovements = $returnedMovementsQuery->get();

        // Limitar los items sólo a aquellos disponibles para devolver
        $filteredItems = $items->map(function ($item) use ($returnedMovements) {
            $returnedQty = $returnedMovements
                ->where('mov_pro_code', $item->mov_pro_code)
                ->where('mov_war_id', $item->mov_war_id)
                ->sum('mov_quantity');

            $remainingQty = max(0, $item->mov_quantity - $returnedQty);
            // Formatear a máximo 4 decimales para javascript
            $item->mov_quantity_remaining = round($remainingQty, 4);
            $item->mov_quantity = $item->mov_quantity_remaining;

            return $item;
        })->filter(function ($item) {
            // Ignorar los items que ya se han devuelto en su totalidad
            return $item->mov_quantity_remaining > 0;
        })->values();

        return response()->json($filteredItems);
    }
}
