<?php

namespace App\Http\Controllers\Sales\SaleOrder;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Inventary\ProType;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\Warehouse;
use App\Services\Finance\RateService;
use App\Services\Sales\SaleOrderService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleOrderController extends Controller
{
    public function __construct(
        protected SaleOrderService $saleOrderService,
        protected RateService $rateService,
    ) {}

    public function index()
    {
        $sales = SaleOrder::with(['customer', 'transactionType'])
            ->where('sls_trans_type_code', 'FACV')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
        ]);
    }

    private function getAvailableProducts()
    {
        $stockByProduct = Stock::where('stk_war_id', 1)
            ->where('stk_cant', '>', 0)
            ->pluck('stk_cant', 'stk_pro_code');

        return Product::with(['category', 'brand'])
            ->where(function ($query) use ($stockByProduct) {
                $query->whereIn('pro_code', $stockByProduct->keys())
                    ->orWhere('pro_type', ProType::COMPUESTO->value);
            })
            ->get()
            ->filter(function ($product) {
                $latestPrice = $product->latestPrice();
                $latestCost = $product->latestCost();

                if ($product->pro_type->value === ProType::COMPUESTO->value) {
                    return $latestPrice && $latestCost;
                }

                return $latestPrice && $latestCost
                    && $latestPrice->price_usd > $latestCost->cost_pro_cost_ref;
            })
            ->map(function ($product) use ($stockByProduct) {
                $latestPrice = $product->latestPrice();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und,
                    'pro_conversion_factor' => $product->pro_conversion_factor ?? 1,
                    'pro_cant_min' => $product->pro_cant_min ?? 0,
                    'pro_iva' => $product->pro_iva,
                    'pro_cat_id' => $product->pro_cat_id,
                    'pro_bra_id' => $product->pro_bra_id,
                    'pro_type' => $product->pro_type,
                    'category' => $product->category,
                    'brand' => $product->brand,
                    'latest_price' => $latestPrice->price_usd,
                    'latest_utility' => $latestPrice->price_utility,
                    'total_stock' => (float) $stockByProduct->get($product->pro_code, 0),
                ];
            })
            ->values();
    }

    public function create()
    {
        return Inertia::render('Sales/Create', [
            'customers' => Customer::all(),
            'products' => $this->getAvailableProducts(),
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

    public function store(StoreSaleRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $sale = $this->saleOrderService->create($validated);

            DB::commit();

            if ($validated['sls_payment_condition'] === 'cash' && (float) $sale->sls_total > 0) {
                $receivable = $sale->accountsReceivable;

                if ($receivable) {
                    return redirect()->route('sales.create')
                        ->with('success', 'Venta guardada exitosamente.')
                        ->with('payment_pending', [
                            'entity' => 'sale',
                            'acr_id' => $receivable->id,
                            'document_number' => $sale->sls_number,
                            'amount' => (float) $sale->sls_total,
                            'currency' => $sale->sls_coin,
                            'date' => $sale->sls_date?->format('Y-m-d'),
                            'valorcam' => (float) $sale->sls_valorcam,
                            'customer_id' => $sale->sls_customer_id,
                        ]);
                }
            }

            return redirect()->route('sales.index')->with('success', 'Venta guardada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar la venta: '.$e->getMessage()]);
        }
    }

    public function show(SaleOrder $sale)
    {
        return redirect()->route('sales.edit', $sale->id);
    }

    public function edit($id)
    {
        $sale = SaleOrder::with(['movements', 'customer', 'transactionType'])->findOrFail($id);

        return Inertia::render('Sales/Edit', [
            'sale' => $sale,
            'customers' => Customer::all(),
            'products' => $this->getAvailableProducts(),
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

    public function update(UpdateSaleRequest $request, $id)
    {
        $sale = SaleOrder::findOrFail($id);
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $this->saleOrderService->update($sale, $validated);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Venta actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la venta: '.$e->getMessage()]);
        }
    }
}
