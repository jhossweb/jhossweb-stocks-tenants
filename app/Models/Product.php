<?php

namespace App\Models;

use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'pro_code',
        'pro_name',
        'pro_und',
        'pro_conversion_factor',
        'pro_cant_min',
        'pro_iva',
        'pro_cat_id',
        'pro_bra_id',
    ];

    protected function casts(): array
    {
        return [
            // Laravel convertirá automáticamente el string de la BD a tu clase Enum
            'pro_und' => UndProduct::class,

            // Ya que tienes TypeIva, aprovecha de agregarlo también
            'pro_iva' => TypeIva::class,
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'stk_pro_code', 'pro_code');
    }

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

    /**
     * Get the price histories for the product.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(PriceHistory::class, 'price_pro_code', 'pro_code');
    }

    /**
     * Get the latest price for the product.
     */
    public function latestPrice(): ?PriceHistory
    {
        return $this->priceHistories()->latest('price_date')->latest('id')->first();
    }
}
