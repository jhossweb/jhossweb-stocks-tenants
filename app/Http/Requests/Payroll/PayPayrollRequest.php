<?php

namespace App\Http\Requests\Payroll;

use App\Enum\TypeCoin;
use App\Models\BankAccount;
use App\Models\Rate;
use Illuminate\Foundation\Http\FormRequest;

class PayPayrollRequest extends FormRequest
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
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'required|string|max:50',
            'description' => 'nullable|string|max:150',
            'payment_currency' => 'required|string|max:10',
            'payment_rate' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $payroll = $this->route('payroll');

            if (! $payroll) {
                return;
            }

            $netSalary = (float) $payroll->pay_net_salary;
            $payCoin = $payroll->pay_coin;
            $paymentRate = (float) ($this->input('payment_rate') ?: 1);
            $amount = (float) $this->input('amount');

            $latestRate = Rate::orderBy('rate_date', 'desc')->first();

            $rateOfPayCoin = match ($payCoin instanceof TypeCoin ? $payCoin->value : $payCoin) {
                'USD' => 1,
                'COP' => (float) ($latestRate?->rate_cop ?? 1),
                'EUR' => (float) ($latestRate?->rate_eur ?? 1),
                default => (float) ($latestRate?->rate_usd ?? 1),
            };

            $amountInUSD = $netSalary / $rateOfPayCoin;
            $expectedAmount = $amountInUSD * $paymentRate;

            if (abs($amount - $expectedAmount) > 0.01) {
                $validator->errors()->add('amount', sprintf(
                    'El monto debe ser igual al sueldo neto convertido (%s %s).',
                    number_format($expectedAmount, 2),
                    $this->input('payment_currency')
                ));
            }
        });
    }
}
