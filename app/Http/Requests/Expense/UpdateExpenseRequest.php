<?php

namespace App\Http\Requests\Expense;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exp_category_id' => 'required|exists:expense_categories,id',
            'exp_description' => 'required|string',
            'exp_amount' => 'required|numeric|min:0',
            'exp_date' => 'required|date',
            'exp_status' => ['required', Rule::in(ExpenseStatus::values())],
            'exp_coin' => ['required', Rule::in(TypeCoin::values())],
            'exp_valorcam' => 'required|numeric|min:0',
            'exp_payment_date' => 'nullable|date',
            'exp_bank_transaction_id' => 'nullable|exists:bank_transactions,id',
        ];
    }
}
