<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollPaymentRequest extends FormRequest
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
            'payment_date' => ['required', 'date'],
            'employee_id' => ['required', 'exists:employees,id'],
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'payment_currency' => ['required', 'string'],
            'payment_rate' => ['required', 'numeric'],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['required', 'string', 'max:50'],
            'payment_description' => ['nullable', 'string', 'max:150'],
            'payments' => ['required', 'array'],
            'payments.*.pay_id' => ['required', 'exists:payrolls,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
