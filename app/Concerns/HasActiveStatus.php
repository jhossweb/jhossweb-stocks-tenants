<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasActiveStatus
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable().'.is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->getTable().'.is_active', false);
    }

    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    public function activate(): void
    {
        $this->forceFill(['is_active' => true])->save();
    }

    public function deactivate(): void
    {
        $this->forceFill(['is_active' => false])->save();
    }
}
