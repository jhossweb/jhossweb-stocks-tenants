<?php

namespace App\Http\Requests\Sales;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sls_number' => 'required|string|max:50|unique:sale_orders,sls_number',
            'sls_date' => 'required|date',
            'sls_customer_id' => 'required|exists:customers,id',
            'sls_valorcam' => 'required|numeric',
            'sls_coin' => 'required|string',
            'sls_subtotal' => 'required|numeric',
            'sls_exento' => 'required|numeric',
            'sls_iva' => 'required|numeric',
            'sls_total' => 'required|numeric',

            'items' => 'required|array|min:1',
            'items.*.mvs_pro_code' => 'required|exists:products,pro_code|distinct',
            'items.*.mvs_war_id' => 'required|exists:warehouses,id',
            'items.*.mvs_quantity' => 'required|numeric|min:0.01',
            'items.*.mvs_unit_price' => 'required|numeric|min:0',
            'items.*.mvs_unit_price_ref' => 'required|numeric|min:0',
            'items.*.mvs_pro_iva' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            foreach ($items as $index => $item) {
                $product = Product::where('pro_code', $item['mvs_pro_code'])->first();
                if (! $product) {
                    continue;
                }

                $latestPrice = $product->latestPrice();
                $latestCost = $product->latestCost();

                if (! $latestPrice) {
                    $validator->errors()->add(
                        "items.{$index}.mvs_unit_price_ref",
                        "El producto {$product->pro_name} no tiene un precio registrado."
                    );

                    continue;
                }

                if ((float) $item['mvs_unit_price_ref'] !== (float) $latestPrice->price_usd) {
                    $validator->errors()->add(
                        "items.{$index}.mvs_unit_price_ref",
                        "El precio del producto {$product->pro_name} no es válido."
                    );
                }

                if ($latestCost && (float) $latestPrice->price_usd <= (float) $latestCost->cost_pro_cost_ref) {
                    $validator->errors()->add(
                        "items.{$index}.mvs_unit_price_ref",
                        "El precio del producto {$product->pro_name} debe ser mayor al costo."
                    );
                }

                $stock = Stock::where('stk_pro_code', $item['mvs_pro_code'])
                    ->where('stk_war_id', 1)
                    ->value('stk_cant') ?? 0;

                if ((float) $item['mvs_quantity'] > (float) $stock) {
                    $validator->errors()->add(
                        "items.{$index}.mvs_quantity",
                        "Stock insuficiente de {$product->pro_name}. Disponible: {$stock}"
                    );
                }
            }
        });
    }
}
