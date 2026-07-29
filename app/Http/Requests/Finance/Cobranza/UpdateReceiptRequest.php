<?php

namespace App\Http\Requests\Finance\Cobranza;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReceiptRequest extends FormRequest
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
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_currency' => ['required', 'string'],
            'payment_reference' => ['required', 'string', 'max:50'],
            'payment_description' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
