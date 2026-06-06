<?php

namespace App\Http\Requests\Expense;

use App\Enum\Expense\ExpenseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exc_name' => 'required|string|max:100',
            'exc_type' => ['required', Rule::in(ExpenseType::values())],
            'exc_description' => 'nullable|string',
        ];
    }
}
