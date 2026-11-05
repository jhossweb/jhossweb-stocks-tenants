<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Services\Documents\DocumentLifecycleService;

trait HasVoidability
{
    /**
     * @var array{can_void: bool, reason: ?string}|null
     */
    private ?array $voidabilityCache = null;

    public function getCanVoidAttribute(): bool
    {
        return $this->voidabilityData()['can_void'];
    }

    public function getVoidBlockedReasonAttribute(): ?string
    {
        return $this->voidabilityData()['reason'];
    }

    /**
     * @return array{can_void: bool, reason: ?string}
     */
    private function voidabilityData(): array
    {
        return $this->voidabilityCache ??= app(DocumentLifecycleService::class)->voidability($this);
    }
}
