<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Tenancy\Subscription;

use App\Enum\Tenancy\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'started_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date', 'after_or_equal:current_period_start'],
            'grace_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
