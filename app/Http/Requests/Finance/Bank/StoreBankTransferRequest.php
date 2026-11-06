<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance\Bank;

use App\Models\BankAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBankTransferRequest extends FormRequest
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
            'bt_date' => ['required', 'date'],
            'bt_source_bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'bt_destination_bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id', 'different:bt_source_bank_account_id'],
            'bt_amount' => ['required', 'numeric', 'gt:0'],
            'bt_reference' => ['nullable', 'string', 'max:50'],
            'bt_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $source = BankAccount::query()->find($this->input('bt_source_bank_account_id'));
                $destination = BankAccount::query()->find($this->input('bt_destination_bank_account_id'));

                if ($source === null || $destination === null) {
                    return;
                }

                if (! $source->bank_status || ! $destination->bank_status) {
                    $validator->errors()->add('bt_source_bank_account_id', 'Solo se permiten transferencias entre cuentas activas.');

                    return;
                }

                if (strtoupper((string) $source->bank_currency) !== strtoupper((string) $destination->bank_currency)) {
                    $validator->errors()->add('bt_destination_bank_account_id', 'Las cuentas deben tener la misma moneda.');

                    return;
                }

                if ((float) $source->bank_balance < (float) $this->input('bt_amount')) {
                    $validator->errors()->add('bt_amount', 'La cuenta origen no tiene saldo suficiente.');
                }
            },
        ];
    }
}
