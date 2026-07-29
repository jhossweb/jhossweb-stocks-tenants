<?php

namespace App\Http\Requests\Finance;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashClosingRequest extends FormRequest
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
            'cc_date' => ['required', 'date', 'before_or_equal:today'],
            'cc_opening_balance' => ['required', 'numeric', 'min:0'],
            'cc_counted_amount' => ['required', 'numeric', 'min:0'],
            'cc_notes' => ['nullable', 'string', 'max:500'],
            'movements' => ['nullable', 'array'],
            'movements.*.cm_type' => ['required', 'in:inflow,outflow'],
            'movements.*.cm_amount' => ['required', 'numeric', 'min:0'],
            'movements.*.cm_description' => ['required', 'string', 'max:200'],
            'movements.*.cm_reference' => ['nullable', 'string', 'max:50'],
        ];
    }
}
