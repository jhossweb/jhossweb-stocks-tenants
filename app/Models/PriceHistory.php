<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_pro_code',
        'price_utility',
        'price_usd',
        'price_date',
        'price_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price_date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'price_pro_code', 'pro_code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'price_user_id', 'id');
    }
}
