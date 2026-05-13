<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostHistory extends Model
{
    /** @use HasFactory<\Database\Factories\CostHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'cost_pro_code',
        'cost_pro_cost',
        'cost_pro_cost_ref',
        'cost_valorcam',
        'cost_currency',
        'cost_date',
        'cost_user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'cost_pro_code', 'pro_code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cost_user_id', 'id');
    }
}
