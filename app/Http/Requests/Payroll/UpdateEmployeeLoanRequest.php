<?php

namespace App\Http\Requests\Payroll;

use App\Enum\Payroll\LoanStatus;
use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'elo_employee_id' => 'required|exists:employees,id',
            'elo_amount' => 'required|numeric|min:0',
            'elo_remaining_balance' => 'required|numeric|min:0',
            'elo_coin' => ['required', Rule::in(TypeCoin::values())],
            'elo_valorcam' => 'required|numeric|min:0',
            'elo_description' => 'nullable|string',
            'elo_status' => ['required', Rule::in(LoanStatus::values())],
        ];
    }
}
