<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantStatusRequest extends FormRequest
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
            'status' => ['required', 'in:active,suspended'],
            'suspended_reason' => ['required_if:status,suspended', 'nullable', 'string', 'max:500'],
        ];
    }
}
