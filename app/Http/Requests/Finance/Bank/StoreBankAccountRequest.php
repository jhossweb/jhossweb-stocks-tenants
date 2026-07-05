<?php

namespace App\Http\Requests\Finance\Bank;

use App\Enum\Finanzas\TypeAccount;
use App\Enum\TypeCoin;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
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
            'bank_name' => ['required', 'string', 'max:150', 'unique:bank_accounts,bank_name'],
            'bank_account_number' => ['required', 'string', 'max:20', 'unique:bank_accounts,bank_account_number'],
            'bank_type' => ['required', 'in:'.implode(',', TypeAccount::values())],
            'bank_currency' => ['required', 'in:'.implode(',', TypeCoin::values())],
            'bank_description' => ['nullable', 'string', 'max:150'],
            'bank_status' => ['required', 'boolean'],
        ];
    }
}
