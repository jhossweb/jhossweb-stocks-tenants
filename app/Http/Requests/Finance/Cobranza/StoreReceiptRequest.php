<?php

namespace App\Http\Requests\Finance\Cobranza;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'customer_id' => ['required', 'exists:customers,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_currency' => ['required', 'string'],
            'payment_rate' => ['required', 'numeric'],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['required', 'string', 'max:50'],
            'payment_description' => ['nullable', 'string', 'max:150'],
            'payments' => ['required', 'array'],
            'payments.*.acr_id' => ['required', 'exists:accounts_receivable,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
