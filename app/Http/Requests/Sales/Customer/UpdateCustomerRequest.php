<?php

namespace App\Http\Requests\Sales\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
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
        $customer = $this->route('customer');

        return [
            'clt_name' => ['required', 'string', 'max:100', 'unique:customers,clt_name,'.$customer->id],
            'clt_address' => ['required', 'string', 'max:255'],
            'clt_phone' => ['required', 'string', 'max:20', 'unique:customers,clt_phone,'.$customer->id],
            'clt_email' => ['required', 'email', 'max:100', 'unique:customers,clt_email,'.$customer->id],
            'clt_website' => ['nullable', 'string', 'max:100'],
        ];
    }
}
