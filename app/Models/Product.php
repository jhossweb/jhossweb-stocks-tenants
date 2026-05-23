<?php

namespace App\Models;

use App\Enum\Inventary\ProType;
use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use Carbon\Carbon;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'pro_type',
    ];

    protected function casts(): array
    {
        return [
            'pro_und' => UndProduct::class,
            'pro_iva' => TypeIva::class,
            'pro_type' => ProType::class,
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

    public function componentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'components_productos',
            'cp_pro_code',
            'cp_comp_pro_code',
            'pro_code',
            'pro_code'
        )->withPivot(['cp_pro_und', 'cp_quantity']);
    }

    public function productosPadre(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'components_productos',
            'cp_comp_pro_code',
            'cp_pro_code',
            'pro_code',
            'pro_code'
        )->withPivot(['cp_pro_und', 'cp_quantity']);
    }

    public function calcularCostoCompuesto(): float
    {
        return $this->componentes->sum(function ($componente) {
            $costoUnitario = $componente->latestCost()?->cost_pro_cost_ref ?? 0;
            return $componente->pivot->cp_quantity * $costoUnitario;
        });
    }

    public function guardarCostoCompuesto(): void
    {
        $costo = $this->calcularCostoCompuesto();

        if ($costo > 0) {
            $this->costHistories()->create([
                'cost_pro_code' => $this->pro_code,
                'cost_pro_cost' => $costo,
                'cost_pro_cost_ref' => $costo,
                'cost_valorcam' => 1,
                'cost_currency' => 'USD',
                'cost_date' => now()->format('Y-m-d'),
                'cost_user_id' => auth()->id(),
            ]);
        }
    }
}
