<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenancy\Plan;

use App\Enum\Tenancy\BillingCycle;
use App\Enum\Tenancy\TenantFeature;
use App\Enum\Tenancy\TenantLimit;
use App\Enum\Tenancy\TenantModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'limits.*' => ['nullable', 'integer', 'min:0'],
            'module_keys' => ['nullable', 'array'],
            'module_keys.*' => [Rule::enum(TenantModule::class)],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::enum(TenantFeature::class)],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $limits = $this->input('limits');

                if (! is_array($limits)) {
                    return;
                }

                $unknown = array_diff(array_keys($limits), TenantLimit::keys());

                if ($unknown !== []) {
                    $validator->errors()->add('limits', 'Los límites contienen claves no válidas.');
                }
            },
        ];
    }
}
