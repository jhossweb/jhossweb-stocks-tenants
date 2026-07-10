<?php

namespace App\Http\Requests\Expense;

use App\Models\BankAccount;
use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class PayExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_account_id' => [
                'required',
                'exists:bank_accounts,id',
                function ($attribute, $value, $fail) {
                    $account = BankAccount::find($value);
                    if (! $account) {
                        return;
                    }
                    $amount = (float) ($this->amount ?? 0);
                    if ($account->bank_balance < $amount) {
                        $fail("Saldo insuficiente en {$account->bank_name}. Saldo disponible: ".number_format((float) $account->bank_balance, 2).' '.$account->bank_currency);
                    }
                },
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $expense = $this->route('expense');
                    if (! $expense || ! $expense instanceof Expense) {
                        $fail('El gasto no fue encontrado.');

                        return;
                    }

                    $rate = (float) ($this->payment_rate ?? 1);
                    $paymentCoin = (string) ($this->payment_currency ?? $expense->exp_coin?->value ?? 'USD');
                    $amountInExpenseCurrency = $expense->convertToExpenseCurrency((float) $value, $rate, $paymentCoin);

                    if ($amountInExpenseCurrency > $expense->exp_remaining_balance) {
                        $fail("El monto en moneda del gasto ({$amountInExpenseCurrency}) supera el saldo pendiente ({$expense->exp_remaining_balance}).");
                    }
                },
            ],
            'reference' => 'required|string|max:50',
            'description' => 'nullable|string|max:150',
            'payment_currency' => 'required|string|max:10',
            'payment_rate' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:30',
        ];
    }
}
