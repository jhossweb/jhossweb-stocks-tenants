<?php

declare(strict_types=1);

namespace App\Http\Requests\Rules;

use App\Enum\Tenancy\TenantLimit;
use App\Services\Tenancy\UsageLimiter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WithinPlanLimit implements ValidationRule
{
    public function __construct(
        private readonly TenantLimit $limit,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $limiter = app(UsageLimiter::class);

        if ($limiter->canCreate($this->limit)) {
            return;
        }

        $fail($limiter->messageFor($this->limit));
    }
}
