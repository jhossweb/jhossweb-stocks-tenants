<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenancy\BillingRate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rate_date' => ['required', 'date', Rule::unique('billing_rates', 'rate_date')->ignore($this->route('rate'))],
            'rate_usd' => ['required', 'numeric', 'min:0'],
            'rate_cop' => ['required', 'numeric', 'min:0'],
            'rate_eur' => ['required', 'numeric', 'min:0'],
            'rate_cop_bs' => ['required', 'numeric', 'min:0'],
        ];
    }
}
