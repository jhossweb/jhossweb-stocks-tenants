<?php

namespace App\Http\Requests\Sales\SaleReturn;

use App\Enum\Tenancy\TenantLimit;
use App\Http\Requests\Rules\WithinPlanLimit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sls_number' => ['required', 'string', 'max:50', 'unique:sale_orders,sls_number', new WithinPlanLimit(TenantLimit::SalesReturnsMonthly)],
            'sls_date' => ['required', 'date'],
            'sls_customer_id' => ['required', 'exists:customers,id'],
            'sls_valorcam' => ['required', 'numeric'],
            'sls_coin' => ['required', 'string'],
            'sls_reference' => ['required', 'exists:sale_orders,sls_number'],
            'sls_subtotal' => ['required', 'numeric'],
            'sls_exento' => ['required', 'numeric'],
            'sls_iva' => ['required', 'numeric'],
            'sls_total' => ['required', 'numeric'],
            'sls_payment_condition' => ['required', 'string', 'in:cash,credit'],
            'sls_credit_days' => ['required', 'integer', 'min:0', 'max:365'],
            'sls_due_date' => ['required', 'date', 'after_or_equal:sls_date'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.mvs_pro_code' => ['required', 'exists:products,pro_code'],
            'items.*.mvs_war_id' => ['required', 'exists:warehouses,id'],
            'items.*.mvs_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.mvs_unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.mvs_unit_price_ref' => ['required', 'numeric', 'min:0'],
            'items.*.mvs_pro_iva' => ['required', 'numeric', 'min:0'],
        ];
    }
}
