<?php

namespace App\Models;

use Database\Factories\MovementsSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementsSale extends Model
{
    /** @use HasFactory<MovementsSaleFactory> */
    use HasFactory;

    protected $fillable = [
        'mvs_number',
        'mvs_trans_type_code',
        'mvs_factor',
        'mvs_pro_code',
        'mvs_pro_iva',
        'mvs_war_id',
        'mvs_quantity',
        'mvs_unit_price',
        'mvs_unit_price_ref',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mvs_pro_code', 'pro_code');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'mvs_war_id', 'id');
    }

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'mvs_number', 'sls_number');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'mvs_trans_type_code', 'trans_type_code');
    }

    public function getMvsQuantityAttribute($value)
    {
        return (float) $value;
    }

    public function getMvsUnitPriceAttribute($value)
    {
        return (float) $value;
    }

    public function getMvsFactorAttribute($value)
    {
        return (int) $value;
    }
}
