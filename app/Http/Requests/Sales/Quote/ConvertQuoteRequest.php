<?php

namespace App\Http\Requests\Sales\Quote;

use Illuminate\Foundation\Http\FormRequest;

class ConvertQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sls_number' => 'required|string|max:50|unique:sale_orders,sls_number',
        ];
    }
}
