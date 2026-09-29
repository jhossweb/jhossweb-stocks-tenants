<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasFactory;

    protected $fillable = [
        'inv_number',
        'inv_date',
        'inv_trans_type_code',
        'inv_notes',
        'inv_status',
    ];

    protected function casts(): array
    {
        return [
            'inv_date' => 'date',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(MovementInventory::class, 'min_inv_number', 'inv_number');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'inv_trans_type_code', 'trans_type_code');
    }
}
