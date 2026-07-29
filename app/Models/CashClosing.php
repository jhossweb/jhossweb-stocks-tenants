<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashClosing extends Model
{
    protected $fillable = [
        'cc_date',
        'cc_opening_balance',
        'cc_total_sales',
        'cc_total_received',
        'cc_total_manual_inflows',
        'cc_total_manual_outflows',
        'cc_expected_closing',
        'cc_counted_amount',
        'cc_difference',
        'cc_status',
        'cc_notes',
        'cc_closed_by',
        'cc_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'cc_date' => 'date:Y-m-d',
            'cc_opening_balance' => 'decimal:4',
            'cc_total_sales' => 'decimal:4',
            'cc_total_received' => 'decimal:4',
            'cc_total_manual_inflows' => 'decimal:4',
            'cc_total_manual_outflows' => 'decimal:4',
            'cc_expected_closing' => 'decimal:4',
            'cc_counted_amount' => 'decimal:4',
            'cc_difference' => 'decimal:4',
            'cc_closed_at' => 'datetime',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'cm_cash_closing_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cc_closed_by');
    }
}
