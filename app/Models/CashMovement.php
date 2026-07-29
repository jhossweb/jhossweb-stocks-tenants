<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    protected $fillable = [
        'cm_cash_closing_id',
        'cm_type',
        'cm_amount',
        'cm_description',
        'cm_reference',
    ];

    protected function casts(): array
    {
        return [
            'cm_amount' => 'decimal:4',
        ];
    }

    public function cashClosing(): BelongsTo
    {
        return $this->belongsTo(CashClosing::class, 'cm_cash_closing_id');
    }
}
