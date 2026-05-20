<?php

namespace App\Http\Controllers\Sales;

use App\Enum\Sales\StatusSales;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSaleRequest;
use App\Http\Requests\Sales\UpdateSaleRequest;
use App\Models\Customer;
use App\Models\MovementsSale;
use App\Models\Product;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleOrderController extends Controller
{
    public function index()
    {
        $sales = SaleOrder::with(['customer', 'transactionType'])
            ->where('sls_trans_type_code', 'facv')
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
            ->whereIn('pro_code', $stockByProduct->keys())
            ->get()
            ->filter(function ($product) {
                $latestPrice = $product->latestPrice();
                $latestCost = $product->latestCost();

                return $latestPrice && $latestCost
                    && $latestPrice->price_usd > $latestCost->cost_pro_cost_ref;
            })
            ->map(function ($product) use ($stockByProduct) {
                $latestPrice = $product->latestPrice();

                return [
                    'pro_code' => $product->pro_code,
                    'pro_name' => $product->pro_name,
                    'pro_und' => $product->pro_und,
                    'pro_iva' => $product->pro_iva,
                    'pro_cat_id' => $product->pro_cat_id,
                    'pro_bra_id' => $product->pro_bra_id,
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
        ]);
    }

    public function store(StoreSaleRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'facv')->first();

            $sale = new SaleOrder;
            $sale->sls_number = $validated['sls_number'];
            $sale->sls_date = $validated['sls_date'];
            $sale->sls_trans_type_code = $transactionType->trans_type_code;
            $sale->sls_customer_id = $validated['sls_customer_id'];
            $sale->sls_valorcam = $validated['sls_valorcam'];
            $sale->sls_coin = $validated['sls_coin'];
            $sale->sls_subtotal = $validated['sls_subtotal'];
            $sale->sls_exento = $validated['sls_exento'];
            $sale->sls_iva = $validated['sls_iva'];
            $sale->sls_total = $validated['sls_total'];
            $sale->sls_status = StatusSales::PENDING->value;
            $sale->save();

            foreach ($validated['items'] as $item) {
                $movement = new MovementsSale;
                $movement->mvs_number = $sale->sls_number;
                $movement->mvs_trans_type_code = $sale->sls_trans_type_code;
                $movement->mvs_factor = $transactionType->trans_type_factor;
                $movement->mvs_pro_code = $item['mvs_pro_code'];
                $movement->mvs_pro_iva = $item['mvs_pro_iva'];
                $movement->mvs_war_id = $item['mvs_war_id'];
                $movement->mvs_quantity = $item['mvs_quantity'];
                $movement->mvs_unit_price = $item['mvs_unit_price'];
                $movement->mvs_unit_price_ref = $item['mvs_unit_price_ref'];
                $movement->save();
            }

            DB::commit();

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

            $transactionType = TransactionType::where('trans_type_code', 'facv')->first();

            $sale->sls_number = $validated['sls_number'];
            $sale->sls_date = $validated['sls_date'];
            $sale->sls_customer_id = $validated['sls_customer_id'];
            $sale->sls_valorcam = $validated['sls_valorcam'];
            $sale->sls_coin = $validated['sls_coin'];
            $sale->sls_subtotal = $validated['sls_subtotal'];
            $sale->sls_exento = $validated['sls_exento'];
            $sale->sls_iva = $validated['sls_iva'];
            $sale->sls_total = $validated['sls_total'];
            $sale->save();

            $existingIds = $sale->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementsSale::find($item['id']);
                } else {
                    $movement = new MovementsSale;
                    $movement->mvs_number = $sale->sls_number;
                    $movement->mvs_trans_type_code = $sale->sls_trans_type_code ?? $transactionType->trans_type_code;
                    $movement->mvs_factor = $transactionType->trans_type_factor;
                }

                $movement->mvs_pro_code = $item['mvs_pro_code'];
                $movement->mvs_pro_iva = $item['mvs_pro_iva'];
                $movement->mvs_war_id = $item['mvs_war_id'];
                $movement->mvs_quantity = $item['mvs_quantity'];
                $movement->mvs_unit_price = $item['mvs_unit_price'];
                $movement->mvs_unit_price_ref = $item['mvs_unit_price_ref'];
                $movement->save();

                $newIds[] = $movement->id;
            }

            $itemsToDelete = array_diff($existingIds, $newIds);
            if (! empty($itemsToDelete)) {
                foreach ($itemsToDelete as $delId) {
                    if ($model = MovementsSale::find($delId)) {
                        $model->delete();
                    }
                }
            }

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Venta actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la venta: '.$e->getMessage()]);
        }
    }
}
