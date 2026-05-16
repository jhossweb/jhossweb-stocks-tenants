<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sls_number' => ['required', 'string', 'max:50', Rule::unique('sale_orders', 'sls_number')->ignore($this->route('sale'))],
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
        ];
    }
}
