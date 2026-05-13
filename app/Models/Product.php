<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'pro_code',
        'pro_name',
        'pro_und',
        'pro_iva',
        'pro_cat_id',
        'pro_bra_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'pro_cat_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'pro_bra_id');
    }

    public function costHistories(): HasMany
    {
        return $this->hasMany(CostHistory::class, 'cost_pro_code', 'pro_code');
    }

    public function latestCost(): ?CostHistory
    {
        return $this->costHistories()->latest('cost_date')->latest('id')->first();
    }

    public function costAtDate(Carbon $date): ?CostHistory
    {
        return $this->costHistories()
            ->where('cost_date', '<=', $date->format('Y-m-d'))
            ->latest('cost_date')
            ->latest('id')
            ->first();
    }
}
