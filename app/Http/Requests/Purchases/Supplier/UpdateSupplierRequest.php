<?php

namespace App\Http\Requests\Purchases\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');

        return [
            'supplier_name' => ['required', 'string', 'max:100', 'unique:suppliers,supplier_name,'.$supplier->id],
            'supplier_address' => ['required', 'string', 'max:255'],
            'supplier_phone' => ['required', 'string', 'max:20', 'unique:suppliers,supplier_phone,'.$supplier->id],
            'supplier_email' => ['required', 'email', 'max:100', 'unique:suppliers,supplier_email,'.$supplier->id],
            'supplier_website' => ['nullable', 'string', 'max:100'],
        ];
    }
}
