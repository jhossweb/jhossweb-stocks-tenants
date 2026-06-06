<?php

namespace App\Http\Requests\Payroll;

use App\Enum\Payroll\DamageStatus;
use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'eda_employee_id' => 'required|exists:employees,id',
            'eda_amount' => 'required|numeric|min:0',
            'eda_remaining_balance' => 'required|numeric|min:0',
            'eda_coin' => ['required', Rule::in(TypeCoin::values())],
            'eda_valorcam' => 'required|numeric|min:0',
            'eda_description' => 'required|string',
            'eda_status' => ['required', Rule::in(DamageStatus::values())],
        ];
    }
}
