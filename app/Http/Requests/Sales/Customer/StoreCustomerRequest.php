<?php

namespace App\Http\Requests\Sales\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'clt_code' => ['nullable', 'string', 'max:50', 'unique:customers,clt_code'],
            'clt_cedula' => ['nullable', 'string', 'max:20'],
            'clt_name' => ['required', 'string', 'max:100'],
            'clt_address' => ['required', 'string', 'max:255'],
            'clt_phone' => ['required', 'string', 'max:20', 'unique:customers,clt_phone'],
            'clt_email' => ['required', 'email', 'max:100', 'unique:customers,clt_email'],
            'clt_website' => ['nullable', 'string', 'max:100'],
            'clt_credit_days' => ['nullable', 'integer', 'min:0', 'max:365'],
        ];
    }
}
