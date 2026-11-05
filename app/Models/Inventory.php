<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    /** @use HasFactory<InventoryFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'inv_number',
        'inv_date',
        'inv_trans_type_code',
        'inv_notes',
        'inv_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
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
