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
            ->whereHas('transactionType', function ($query) {
                $query->where('trans_type_factor', '-1');
            })
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

    public function getUnpaidInvoices(Supplier $supplier)
    {
        $invoices = PurchaseOrder::where('invoice_supplier_id', $supplier->id)
            ->where('invoice_status', '!=', StatusPurchases::PAID->value)
            ->whereHas('transactionType', function ($query) {
                $query->where('trans_type_factor', 1);
            })
            ->get();

        return response()->json($invoices);
    }

    public function getPurchaseOrderItems(PurchaseOrder $purchaseOrder)
    {
        $items = $purchaseOrder->movements()->with(['product', 'warehouse'])->get();

        return response()->json($items);
    }
}
