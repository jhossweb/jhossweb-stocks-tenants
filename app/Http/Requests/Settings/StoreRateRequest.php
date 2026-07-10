<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRateRequest extends FormRequest
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
            'rate_date' => ['required', 'date', 'unique:rates,rate_date'],
            'rate_usd' => ['required', 'numeric', 'min:0'],
            'rate_cop' => ['required', 'numeric', 'min:0'],
            'rate_eur' => ['required', 'numeric', 'min:0'],
            'rate_cop_bs' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
