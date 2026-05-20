<?php

namespace App\Http\Controllers\Sales;

use App\Enum\Sales\StatusSales;
use App\Enum\TypeCoin;
use App\Enum\TypeIva;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MovementsSale;
use App\Models\Product;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\TransactionType;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleReturnController extends Controller
{
    public function index()
    {
        $returns = SaleOrder::with(['customer', 'movements.product', 'movements.warehouse'])
            ->where('sls_trans_type_code', 'devv')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('SaleReturns/Index', [
            'returns' => $returns,
        ]);
    }

    public function create()
    {
        $stockByProduct = Stock::where('stk_war_id', 1)
            ->where('stk_cant', '>', 0)
            ->pluck('stk_cant', 'stk_pro_code');

        $products = Product::with(['category', 'brand'])
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

        return Inertia::render('SaleReturns/Create', [
            'customers' => Customer::all(),
            'products' => $products,
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sls_number' => 'required|string|max:50|unique:sale_orders,sls_number',
            'sls_date' => 'required|date',
            'sls_customer_id' => 'required|exists:customers,id',
            'sls_valorcam' => 'required|numeric',
            'sls_coin' => 'required|string',
            'sls_reference' => 'required|exists:sale_orders,sls_number',
            'sls_subtotal' => 'required|numeric',
            'sls_exento' => 'required|numeric',
            'sls_iva' => 'required|numeric',
            'sls_total' => 'required|numeric',

            'items' => 'required|array|min:1',
            'items.*.mvs_pro_code' => 'required|exists:products,pro_code',
            'items.*.mvs_war_id' => 'required|exists:warehouses,id',
            'items.*.mvs_quantity' => 'required|numeric|min:0.01',
            'items.*.mvs_unit_price' => 'required|numeric|min:0',
            'items.*.mvs_unit_price_ref' => 'required|numeric|min:0',
            'items.*.mvs_pro_iva' => 'required|numeric|min:0',
        ]);

        $originalSale = SaleOrder::where('sls_number', $validated['sls_reference'])
            ->where('sls_trans_type_code', 'facv')
            ->first();

        if (! $originalSale) {
            return back()->withErrors(['sls_reference' => 'La factura de venta referenciada no existe o no es válida.']);
        }

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'devv')->first();

            $return = new SaleOrder;
            $return->sls_number = $validated['sls_number'];
            $return->sls_date = $validated['sls_date'];
            $return->sls_trans_type_code = $transactionType->trans_type_code;
            $return->sls_customer_id = $validated['sls_customer_id'];
            $return->sls_valorcam = $validated['sls_valorcam'];
            $return->sls_coin = $validated['sls_coin'];
            $return->sls_reference = $validated['sls_reference'];
            $return->sls_subtotal = $validated['sls_subtotal'];
            $return->sls_exento = $validated['sls_exento'];
            $return->sls_iva = $validated['sls_iva'];
            $return->sls_total = $validated['sls_total'];
            $return->sls_status = StatusSales::PENDING->value;
            $return->save();

            foreach ($validated['items'] as $item) {
                $originalQty = MovementsSale::where('mvs_number', $validated['sls_reference'])
                    ->where('mvs_pro_code', $item['mvs_pro_code'])
                    ->sum('mvs_quantity');

                $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($validated) {
                    $q->where('sls_reference', $validated['sls_reference']);
                })
                    ->where('mvs_pro_code', $item['mvs_pro_code'])
                    ->sum('mvs_quantity');

                $availableQty = $originalQty - $returnedQty;

                if ($item['mvs_quantity'] > $availableQty) {
                    throw new \Exception("La cantidad del producto {$item['mvs_pro_code']} excede la disponible para devolver. Disponible: {$availableQty}");
                }

                $movement = new MovementsSale;
                $movement->mvs_number = $return->sls_number;
                $movement->mvs_trans_type_code = $return->sls_trans_type_code;
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

            return redirect()->route('sale-returns.index')->with('success', 'Devolución guardada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al guardar la devolución: '.$e->getMessage()]);
        }
    }

    public function show(SaleOrder $saleReturn)
    {
        return redirect()->route('sale-returns.edit', $saleReturn->id);
    }

    public function edit($id)
    {
        $return = SaleOrder::with(['movements.product', 'movements.warehouse', 'customer', 'transactionType'])->findOrFail($id);

        $stockByProduct = Stock::where('stk_war_id', 1)
            ->where('stk_cant', '>', 0)
            ->pluck('stk_cant', 'stk_pro_code');

        $products = Product::with(['category', 'brand'])
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

        return Inertia::render('SaleReturns/Edit', [
            'saleReturn' => $return,
            'customers' => Customer::all(),
            'products' => $products,
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
        $return = SaleOrder::findOrFail($id);

        $validated = $request->validate([
            'sls_number' => 'required|string|max:50|unique:sale_orders,sls_number,'.$id,
            'sls_date' => 'required|date',
            'sls_customer_id' => 'required|exists:customers,id',
            'sls_valorcam' => 'required|numeric',
            'sls_coin' => 'required|string',
            'sls_subtotal' => 'required|numeric',
            'sls_exento' => 'required|numeric',
            'sls_iva' => 'required|numeric',
            'sls_total' => 'required|numeric',

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:movements_sales,id',
            'items.*.mvs_pro_code' => 'required|exists:products,pro_code',
            'items.*.mvs_war_id' => 'required|exists:warehouses,id',
            'items.*.mvs_quantity' => 'required|numeric|min:0.01',
            'items.*.mvs_unit_price' => 'required|numeric|min:0',
            'items.*.mvs_unit_price_ref' => 'required|numeric|min:0',
            'items.*.mvs_pro_iva' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $transactionType = TransactionType::where('trans_type_code', 'devv')->first();

            $return->sls_number = $validated['sls_number'];
            $return->sls_date = $validated['sls_date'];
            $return->sls_customer_id = $validated['sls_customer_id'];
            $return->sls_valorcam = $validated['sls_valorcam'];
            $return->sls_coin = $validated['sls_coin'];
            $return->sls_subtotal = $validated['sls_subtotal'];
            $return->sls_exento = $validated['sls_exento'];
            $return->sls_iva = $validated['sls_iva'];
            $return->sls_total = $validated['sls_total'];
            $return->save();

            $existingIds = $return->movements()->pluck('id')->toArray();
            $newIds = [];

            foreach ($validated['items'] as $item) {
                if (isset($item['id']) && in_array($item['id'], $existingIds)) {
                    $movement = MovementsSale::find($item['id']);
                } else {
                    $movement = new MovementsSale;
                    $movement->mvs_number = $return->sls_number;
                    $movement->mvs_trans_type_code = $return->sls_trans_type_code ?? $transactionType->trans_type_code;
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

            return redirect()->route('sale-returns.index')->with('success', 'Devolución actualizada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al actualizar la devolución: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $return = SaleOrder::findOrFail($id);

        try {
            DB::beginTransaction();
            $return->movements()->delete();
            $return->delete();
            DB::commit();

            return redirect()->route('sale-returns.index')->with('success', 'Devolución eliminada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Ocurrió un error al eliminar la devolución: '.$e->getMessage()]);
        }
    }

    public function getUnpaidSales(Customer $customer)
    {
        $sales = SaleOrder::with(['movements'])
            ->where('sls_customer_id', $customer->id)
            ->where('sls_trans_type_code', 'facv')
            ->get()
            ->filter(function ($sale) {
                foreach ($sale->movements as $movement) {
                    $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($sale) {
                        $q->where('sls_reference', $sale->sls_number);
                    })
                        ->where('mvs_pro_code', $movement->mvs_pro_code)
                        ->sum('mvs_quantity');

                    if ($movement->mvs_quantity > $returnedQty) {
                        return true;
                    }
                }

                return false;
            })
            ->values()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'sls_number' => $sale->sls_number,
                    'sls_date' => $sale->sls_date,
                    'sls_total' => $sale->sls_total,
                    'sls_coin' => $sale->sls_coin,
                ];
            });

        return response()->json($sales);
    }

    public function getSaleOrderByNumber($sls_number)
    {
        $sale = SaleOrder::with(['movements', 'customer'])
            ->where('sls_number', $sls_number)
            ->where('sls_trans_type_code', 'facv')
            ->first();

        if (! $sale) {
            return response()->json(['error' => 'Factura de venta no encontrada'], 404);
        }

        return response()->json($sale);
    }

    public function getSaleOrderItems(SaleOrder $saleOrder, Request $request)
    {
        $excludeReturnId = $request->input('exclude_return_id');

        $items = $saleOrder->movements()
            ->with(['product', 'warehouse'])
            ->get()
            ->map(function ($movement) use ($saleOrder, $excludeReturnId) {
                $returnedQty = MovementsSale::whereHas('saleOrder', function ($q) use ($saleOrder, $excludeReturnId) {
                    $q->where('sls_reference', $saleOrder->sls_number);
                    if ($excludeReturnId) {
                        $q->where('id', '!=', $excludeReturnId);
                    }
                })
                    ->where('mvs_pro_code', $movement->mvs_pro_code)
                    ->sum('mvs_quantity');

                return [
                    'mvs_pro_code' => $movement->mvs_pro_code,
                    'pro_name' => $movement->product?->pro_name,
                    'pro_iva' => $movement->mvs_pro_iva,
                    'mvs_war_id' => $movement->mvs_war_id,
                    'war_name' => $movement->warehouse?->war_name ?? $movement->warehouse?->id,
                    'mvs_quantity' => $movement->mvs_quantity,
                    'mvs_unit_price' => $movement->mvs_unit_price,
                    'mvs_unit_price_ref' => $movement->mvs_unit_price_ref,
                    'available_qty' => $movement->mvs_quantity - $returnedQty,
                ];
            })
            ->filter(function ($item) {
                return $item['available_qty'] > 0;
            })
            ->values();

        return response()->json($items);
    }
}
