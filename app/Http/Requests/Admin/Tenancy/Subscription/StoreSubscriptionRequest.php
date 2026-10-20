<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenancy\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
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
            'tenant_id' => ['required', 'string', 'exists:tenants,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'started_at' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date', 'after_or_equal:started_at'],
            'grace_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
