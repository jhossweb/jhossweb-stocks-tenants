<?php

namespace App\Http\Controllers\Purchase;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Services\Purchases\PurchaseOrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $purchaseOrderService,
    ) {}

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
        return Inertia::render('Purchases/Create', [
            'suppliers' => Supplier::all(),
            'products' => $this->productCatalog(),
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
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'methods' => TransactionMethod::values(),
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
            'invoice_payment_condition' => ['required', 'string', 'in:cash,credit'],
            'invoice_credit_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_due_date' => ['required', 'date', 'after_or_equal:invoice_date'],

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

            $purchase = $this->purchaseOrderService->create($validated);

            DB::commit();

            if ($validated['invoice_payment_condition'] === 'cash' && (float) $purchase->invoice_total > 0) {
                $payable = $purchase->accountsPayable;

                if ($payable) {
                    return redirect()->route('purchases.create')
                        ->with('success', 'Compra guardada exitosamente.')
                        ->with('payment_pending', [
                            'entity' => 'purchase',
                            'acp_id' => $payable->id,
                            'document_number' => $purchase->invoice_number,
                            'amount' => (float) $purchase->invoice_total,
                            'currency' => $purchase->invoice_coin,
                            'date' => $purchase->invoice_date?->format('Y-m-d'),
                            'valorcam' => (float) $purchase->invoice_valorcam,
                            'supplier_id' => $purchase->invoice_supplier_id,
                        ]);
                }
            }

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
            'products' => $this->productCatalog(),
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
            'invoice_number' => ['required', 'string', 'max:50', Rule::unique('purchase_orders', 'invoice_number')->ignore($purchase->id)],
            'invoice_date' => 'required|date',
            'invoice_supplier_id' => 'required|exists:suppliers,id',
            'invoice_valorcam' => 'required|numeric',
            'invoice_coin' => 'required|string',
            'invoice_subtotal' => 'required|numeric',
            'invoice_exento' => 'required|numeric',
            'invoice_iva' => 'required|numeric',
            'invoice_total' => 'required|numeric',
            'invoice_payment_condition' => ['required', 'string', 'in:cash,credit'],
            'invoice_credit_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_due_date' => ['required', 'date', 'after_or_equal:invoice_date'],

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

            $this->purchaseOrderService->update($purchase, $validated);

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Compra actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la compra: '.$e->getMessage()]);
        }
    }

    private function productCatalog(): Collection
    {
        return Product::all(['pro_code', 'pro_name', 'pro_und', 'pro_iva', 'pro_conversion_factor']);
    }
}
