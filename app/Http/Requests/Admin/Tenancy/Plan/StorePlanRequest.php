<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenancy\Plan;

use App\Enum\Tenancy\BillingCycle;
use App\Enum\Tenancy\TenantModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:50', 'unique:plans,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'price_usd' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'limits' => ['nullable', 'array'],
            'module_keys' => ['nullable', 'array'],
            'module_keys.*' => [Rule::enum(TenantModule::class)],
        ];
    }
}
