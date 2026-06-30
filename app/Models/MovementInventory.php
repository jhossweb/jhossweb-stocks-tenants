<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovementInventory extends Model
{

    protected $table = 'movements_inventories';

    protected $fillable = [
        'min_inv_number',
        'min_trans_type_code',
        'min_factor',
        'min_pro_code',
        'min_war_id',
        'min_quantity',
        'min_reason_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'min_pro_code', 'pro_code');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'min_war_id', 'id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'min_inv_number', 'inv_number');
    }

    public function reason()
    {
        return $this->belongsTo(AdjustmentReason::class, 'min_reason_id', 'id');
    }

    public function getMinQuantityAttribute($value)
    {
        return (float) $value;
    }

    public function getMinFactorAttribute($value)
    {
        return (int) $value;
    }
}
