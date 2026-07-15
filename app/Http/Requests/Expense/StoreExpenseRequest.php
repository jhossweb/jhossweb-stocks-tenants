<?php

namespace App\Http\Requests\Expense;

use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exp_category_id' => 'required|exists:expense_categories,id',
            'exp_description' => 'required|string|max:255',
            'exp_amount' => 'required|numeric|min:0.01',
            'exp_date' => 'required|date',
            'exp_coin' => ['required', Rule::in(TypeCoin::values())],
        ];
    }
}
