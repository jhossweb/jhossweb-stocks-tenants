<?php

namespace App\Http\Requests\Sales\Quote;

use App\Enum\Tenancy\TenantLimit;
use App\Http\Requests\Rules\WithinPlanLimit;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sls_date' => ['required', 'date', new WithinPlanLimit(TenantLimit::SalesQuotesMonthly)],
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
}
