<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenant;

use App\Enum\Tenancy\TenantLimit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTenantLimitOverridesRequest extends FormRequest
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
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $overrides = $this->input('overrides');

                if (! is_array($overrides)) {
                    return;
                }

                $unknown = array_diff(array_keys($overrides), TenantLimit::keys());

                if ($unknown !== []) {
                    $validator->errors()->add('overrides', 'Los overrides contienen claves no válidas.');
                }
            },
        ];
    }
}
