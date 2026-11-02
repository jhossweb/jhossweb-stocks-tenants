<?php

namespace App\Http\Requests\Purchases\Supplier;

use App\Enum\Tenancy\TenantLimit;
use App\Http\Requests\Rules\WithinPlanLimit;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => ['required', 'string', 'max:100', new WithinPlanLimit(TenantLimit::SuppliersMaxTotal)],
            'supplier_address' => ['required', 'string', 'max:255'],
            'supplier_phone' => ['required', 'string', 'max:20', 'unique:suppliers,supplier_phone'],
            'supplier_email' => ['required', 'email', 'max:100', 'unique:suppliers,supplier_email'],
            'supplier_website' => ['nullable', 'string', 'max:100'],
            'supplier_credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
