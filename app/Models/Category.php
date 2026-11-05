<?php

namespace App\Models;

use App\Concerns\HasActiveStatus;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasActiveStatus, HasFactory;

    protected $fillable = [
        'is_active',
        'cat_name',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'pro_cat_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
