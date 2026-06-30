<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
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

    public function movements()
    {
        return $this->hasMany(MovementInventory::class, 'min_inv_number', 'inv_number');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'inv_trans_type_code', 'trans_type_code');
    }
}
