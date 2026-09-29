<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdjustmentReasonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjustmentReason extends Model
{
    /** @use HasFactory<AdjustmentReasonFactory> */
    use HasFactory;

    protected $table = 'adjustment_reasons';

    protected $fillable = [
        'adjr_code',
        'adjr_name',
        'adjr_description',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(MovementInventory::class, 'min_reason_id', 'id');
    }

    /**
     * @param  Builder<AdjustmentReason>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('adjr_name');
    }
}
