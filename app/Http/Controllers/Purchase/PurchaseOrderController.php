<?php

namespace App\Http\Controllers\Purchase;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Models\BankAccount;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Services\Purchases\PurchaseOrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
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
            'suppliers' => Supplier::active()->get(),
            'products' => $this->productCatalog(),
            'warehouses' => Warehouse::active()->get(),
            'transactionTypes' => TransactionType::all(),
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
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $purchase = $this->purchaseOrderService->createWithPayment($validated);

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
        $purchase = PurchaseOrder::with(['movements', 'supplier', 'transactionType', 'accountsPayable'])->findOrFail($id);

        return Inertia::render('Purchases/Edit', [
            'purchase' => $purchase,
            'suppliers' => Supplier::active()->get(),
            'products' => $this->productCatalog(),
            'warehouses' => Warehouse::active()->get(),
            'transactionTypes' => TransactionType::all(),
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
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function update(UpdatePurchaseRequest $request, $id)
    {
        $purchase = PurchaseOrder::findOrFail($id);
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $this->purchaseOrderService->updateWithPayment($purchase, $validated);

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Compra actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la compra: '.$e->getMessage()]);
        }
    }

    private function productCatalog(): Collection
    {
        return Product::active()->get(['pro_code', 'pro_name', 'pro_und', 'pro_iva', 'pro_conversion_factor']);
    }
}
