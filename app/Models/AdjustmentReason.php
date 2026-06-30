<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdjustmentReason extends Model
{
    protected $fillable = [
        'adjr_code',
        'adjr_name',
        'adjr_description',
    ];

    public function movements()
    {
        return $this->hasMany(MovementInventory::class, 'min_reason_id', 'id');
    }
}
