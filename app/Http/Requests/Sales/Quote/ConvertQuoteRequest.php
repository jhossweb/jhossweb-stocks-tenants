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
            'sls_date' => 'required|date',
            'sls_payment_condition' => 'required|string|in:cash,credit',
            'sls_credit_days' => 'required_if:sls_payment_condition,credit|integer|min:0|max:365',
            'sls_due_date' => 'required_if:sls_payment_condition,credit|date|after_or_equal:sls_date',
        ];
    }
}
