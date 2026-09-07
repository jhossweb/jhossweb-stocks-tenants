<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory;

    protected $fillable = [
        'war_name',
    ];

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'stk_war_id');
    }

    public function purchaseMovements(): HasMany
    {
        return $this->hasMany(MovementsPurchase::class, 'mov_war_id');
    }

    public function salesMovements(): HasMany
    {
        return $this->hasMany(MovementsSale::class, 'mvs_war_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(MovementInventory::class, 'min_war_id');
    }

    /**
     * Determines whether the warehouse has stock or recorded movements,
     * in which case it must not be deleted.
     */
    public function isInUse(): bool
    {
        return $this->stocks()->exists()
            || $this->purchaseMovements()->exists()
            || $this->salesMovements()->exists()
            || $this->inventoryMovements()->exists();
    }
}
