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
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.acr_id' => ['required', 'exists:accounts_receivable,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'bank_movements' => ['required', 'array', 'min:1'],
            'bank_movements.*.bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'bank_movements.*.currency' => ['required', 'string'],
            'bank_movements.*.amount' => ['required', 'numeric', 'min:0.0001'],
            'bank_movements.*.method' => ['required', 'string'],
            'bank_movements.*.reference' => ['nullable', 'string', 'max:50'],
            'bank_movements.*.description' => ['nullable', 'string', 'max:150'],
            'apply_vuelto' => ['nullable', 'boolean'],
            'vueltos' => ['nullable', 'array'],
        ];
    }
}
