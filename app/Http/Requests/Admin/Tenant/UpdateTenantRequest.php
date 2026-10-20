<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
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
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:50'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'admin_email')->ignore($tenantId)],
            'status' => ['nullable', 'in:pending,active,suspended'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}
