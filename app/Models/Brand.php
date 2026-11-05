<?php

namespace App\Models;

use App\Concerns\HasActiveStatus;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasActiveStatus, HasFactory;

    protected $fillable = [
        'is_active',
        'bra_name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'pro_bra_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
