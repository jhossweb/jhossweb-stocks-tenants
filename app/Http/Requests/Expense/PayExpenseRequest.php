<?php

namespace App\Http\Requests\Expense;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Rate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $expense = $this->route('expense');
                    if (!$expense || !$expense instanceof Expense) {
                        $fail('El gasto no fue encontrado.');
                        return;
                    }

                    $rate = (float) ($this->payment_rate ?? 1);
                    $amountInExpenseCurrency = $expense->convertToExpenseCurrency((float) $value, $rate);

                    if ($amountInExpenseCurrency > $expense->exp_remaining_balance) {
                        $fail("El monto en moneda del gasto ({$amountInExpenseCurrency}) supera el saldo pendiente ({$expense->exp_remaining_balance}).");
                    }
                },
            ],
            'reference' => 'required|string|max:50',
            'description' => 'nullable|string|max:150',
            'payment_currency' => 'required|string|max:10',
            'payment_rate' => 'required|numeric|min:0',
        ];
    }
}
