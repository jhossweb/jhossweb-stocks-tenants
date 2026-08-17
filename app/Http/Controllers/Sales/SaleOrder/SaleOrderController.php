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
use App\Reports\Sales\SaleInvoiceReport;
use App\Services\Finance\RateService;
use App\Services\Sales\SaleOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleOrderController extends Controller
{
    public function __construct(
        protected SaleOrderService $saleOrderService,
        protected RateService $rateService,
        protected SaleInvoiceReport $saleInvoiceReport,
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

    public function create(Request $request)
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
                    'rate_cop_bs' => $rate->rate_cop_bs,
                ];
            }),
            'latest_rate' => Rate::orderBy('rate_date', 'desc')->first(),
            'coins' => TypeCoin::values(),
            'ivas' => TypeIva::values(),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'methods' => TransactionMethod::values(),
            'sale_preset' => $this->buildSalePreset($request),
        ]);
    }

    /**
     * Builds a prefilled sale draft from an originating quote (cash flow).
     *
     * @return array<string, mixed>|null
     */
    private function buildSalePreset(Request $request): ?array
    {
        if (! $request->has('from_quote')) {
            return null;
        }

        $quote = SaleOrder::with(['customer', 'movements'])
            ->where('sls_trans_type_code', 'COT')
            ->findOrFail($request->integer('from_quote'));

        return [
            'sls_number' => $request->input('sls_number') ?? '',
            'sls_date' => $quote->sls_date?->format('Y-m-d'),
            'sls_customer_id' => $quote->sls_customer_id,
            'sls_valorcam' => $quote->sls_valorcam,
            'sls_coin' => $quote->sls_coin,
            'sls_subtotal' => (float) $quote->sls_subtotal,
            'sls_exento' => (float) $quote->sls_exento,
            'sls_iva' => (float) $quote->sls_iva,
            'sls_total' => (float) $quote->sls_total,
            'sls_payment_condition' => 'cash',
            'sls_credit_days' => 0,
            'sls_due_date' => $quote->sls_date?->format('Y-m-d'),
            'sls_reference' => $quote->sls_number,
            'items' => $quote->movements->map(function ($movement) {
                return [
                    'mvs_pro_code' => $movement->mvs_pro_code,
                    'mvs_war_id' => $movement->mvs_war_id,
                    'mvs_quantity' => (float) $movement->mvs_quantity,
                    'mvs_unit_price' => (float) $movement->mvs_unit_price,
                    'mvs_unit_price_ref' => (float) $movement->mvs_unit_price_ref,
                    'mvs_pro_iva' => (float) $movement->mvs_pro_iva,
                ];
            })->all(),
        ];
    }

    public function store(StoreSaleRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $sale = $this->saleOrderService->createWithPayment($validated);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Venta guardada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar la venta: '.$e->getMessage()]);
        }
    }

    public function show(SaleOrder $sale)
    {
        $sale->load(['customer', 'transactionType', 'movements.product']);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    public function exportPdf(SaleOrder $sale)
    {
        return $this->saleInvoiceReport->download($sale);
    }

    public function edit($id)
    {
        $sale = SaleOrder::with(['movements', 'customer', 'transactionType', 'accountsReceivable'])->findOrFail($id);

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

    public function update(UpdateSaleRequest $request, $id)
    {
        $sale = SaleOrder::findOrFail($id);
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $this->saleOrderService->updateWithPayment($sale, $validated);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Venta actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la venta: '.$e->getMessage()]);
        }
    }
}
