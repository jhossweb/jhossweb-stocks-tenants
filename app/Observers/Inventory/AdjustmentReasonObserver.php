<?php

declare(strict_types=1);

namespace App\Observers\Inventory;

use App\Models\AdjustmentReason;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Invalida el catálogo cacheado de razones cuando cambia la tabla.
 */
class AdjustmentReasonObserver
{
    public function saved(AdjustmentReason $reason): void
    {
        $this->forgetReasons();
    }

    public function deleted(AdjustmentReason $reason): void
    {
        $this->forgetReasons();
    }

    private function forgetReasons(): void
    {
        Cache::forget(InventoryAdjustmentRepository::reasonsCacheKey());
    }
}
