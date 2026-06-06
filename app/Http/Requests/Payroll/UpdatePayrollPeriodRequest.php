<?php

namespace App\Http\Requests\Payroll;

use App\Enum\Payroll\PayrollPeriodStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePayrollPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pp_start_date' => 'required|date',
            'pp_end_date' => 'required|date|after_or_equal:pp_start_date',
            'pp_payment_date' => 'required|date|after_or_equal:pp_end_date',
            'pp_status' => ['required', Rule::in(PayrollPeriodStatus::values())],
        ];
    }
}
