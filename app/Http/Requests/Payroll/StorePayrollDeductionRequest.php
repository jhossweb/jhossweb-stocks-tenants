<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pde_deductible_type' => 'required|string|in:App\\Models\\EmployeeLoan,App\\Models\\EmployeeDamage',
            'pde_deductible_id' => 'required|integer|min:1',
            'pde_percentage' => 'required|numeric|min:0|max:100',
        ];
    }
}
