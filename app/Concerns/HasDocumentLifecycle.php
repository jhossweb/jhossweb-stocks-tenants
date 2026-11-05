<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDocumentLifecycle
{
    public function scopeActiveDocument(Builder $query): Builder
    {
        return $query->where($this->getTable().'.is_active', true);
    }

    public function scopeNotVoided(Builder $query): Builder
    {
        return $query->whereNull($this->getTable().'.voided_at');
    }

    public function isActiveDocument(): bool
    {
        return (bool) ($this->is_active ?? true) && $this->voided_at === null;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
