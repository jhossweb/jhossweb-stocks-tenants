<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementsSale extends Model
{
    /** @use HasFactory<\Database\Factories\MovementsSaleFactory> */
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

    function product()
    {
        return $this->belongsTo(Product::class, 'mvs_pro_code', 'pro_code');
    }

    function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'mvs_war_id', 'id');
    }

    function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'mvs_number', 'sls_number');
    }

    function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'mvs_trans_type_code', 'trans_type_code');
    }

    function getMvsQuantityAttribute($value)
    {
        return (float) $value;
    }

    function getMvsUnitPriceAttribute($value)
    {
        return (float) $value;
    }

    function getMvsFactorAttribute($value)
    {
        return (int) $value;
    }
}
