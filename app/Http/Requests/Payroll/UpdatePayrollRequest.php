<?php

namespace App\Http\Requests\Payroll;

use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pay_employee_id' => 'required|exists:employees,id',
            'pay_period_id' => 'required|exists:payroll_periods,id',
            'pay_base_salary' => 'required|numeric|min:0',
            'pay_coin' => ['required', Rule::in(TypeCoin::values())],
            'pay_valorcam' => 'required|numeric|min:0',
            'pay_total_deductions' => 'required|numeric|min:0',
            'pay_net_salary' => 'required|numeric|min:0',
            'pay_status' => ['required', Rule::in(PayrollStatus::values())],
            'pay_bank_transaction_id' => 'nullable|exists:bank_transactions,id',
        ];
    }
}
