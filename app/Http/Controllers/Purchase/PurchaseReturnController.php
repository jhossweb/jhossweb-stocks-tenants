<?php

namespace App\Http\Controllers\Purchase;

use App\DTOs\Purchases\PurchaseReturnDTO;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\PurchaseReturnStoreRequest;
use App\Http\Requests\Purchase\PurchaseReturnUpdateRequest;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Purchases\PurchaseReturnService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PurchaseReturnService $purchaseReturnService,
    ) {}

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
                    'rate_cop_bs' => $rate->rate_cop_bs,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function store(PurchaseReturnStoreRequest $request)
    {
        $dto = PurchaseReturnDTO::fromArray($request->validated());

        try {
            $this->purchaseReturnService->store($dto);

            return redirect()->route('purchase-returns.index')->with('success', 'Devolución de compra guardada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
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
                    'rate_cop_bs' => $rate->rate_cop_bs,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
        ]);
    }

    public function update(PurchaseReturnUpdateRequest $request, $id)
    {
        $purchaseReturn = PurchaseOrder::findOrFail($id);
        $dto = PurchaseReturnDTO::fromArray($request->validated());

        try {
            $this->purchaseReturnService->update($purchaseReturn, $dto);

            return redirect()->route('purchase-returns.index')->with('success', 'Devolución de compra actualizada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $purchaseReturn = PurchaseOrder::findOrFail($id);

        try {
            $this->purchaseReturnService->destroy($purchaseReturn);

            return redirect()->route('purchase-returns.index')->with('success', 'Devolución de compra eliminada exitosamente.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function getUnpaidInvoices(Supplier $supplier, Request $request)
    {
        $currentInvoiceId = $request->query('current_invoice_id');

        $invoices = PurchaseOrder::where('invoice_supplier_id', $supplier->id)
            ->whereHas('transactionType', fn ($q) => $q->where('trans_type_factor', 1))
            ->withSum('movements', 'mov_quantity')
            ->withSum('returnedMovements', 'mov_quantity')
            ->get()
            ->filter(function ($invoice) use ($currentInvoiceId) {
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

        $returnedMovementsQuery = $purchaseOrder->returnedMovements();

        if ($excludeReturnId) {
            $returnedMovementsQuery->where('purchase_orders.id', '!=', $excludeReturnId);
        }

        $returnedMovements = $returnedMovementsQuery->get();

        $filteredItems = $items->map(function ($item) use ($returnedMovements) {
            $returnedQty = $returnedMovements
                ->where('mov_pro_code', $item->mov_pro_code)
                ->where('mov_war_id', $item->mov_war_id)
                ->sum('mov_quantity');

            $remainingQty = max(0, $item->mov_quantity - $returnedQty);
            $item->mov_quantity_remaining = round($remainingQty, 4);
            $item->mov_quantity = $item->mov_quantity_remaining;

            return $item;
        })->filter(function ($item) {
            return $item->mov_quantity_remaining > 0;
        })->values();

        return response()->json($filteredItems);
    }
}
