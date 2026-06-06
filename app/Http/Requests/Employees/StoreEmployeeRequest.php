<?php

namespace App\Http\Requests\Employees;

use App\Enum\Employees\EmployeeStatus;
use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emp_name' => 'required|string|max:100',
            'emp_document' => 'required|string|max:20|unique:employees,emp_document',
            'emp_phone' => 'required|string|max:20',
            'emp_email' => 'required|email|max:100|unique:employees,emp_email',
            'emp_address' => 'required|string|max:200',
            'emp_position' => 'required|string|max:100',
            'emp_hire_date' => 'required|date',
            'emp_base_salary' => 'required|numeric|min:0',
            'emp_salary_coin' => ['required', Rule::in(TypeCoin::values())],
            'emp_status' => ['required', Rule::in(EmployeeStatus::values())],
        ];
    }
}
