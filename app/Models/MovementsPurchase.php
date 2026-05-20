<?php

namespace App\Models;

use Database\Factories\MovementsPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovementsPurchase extends Model
{
    /** @use HasFactory<MovementsPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'mov_invoice_number',
        'mov_invoice_trans_type_code',
        'mov_factor',
        'mov_pro_code',
        'mov_pro_iva',
        'mov_war_id',
        'mov_quantity',
        'mov_unit_cost',
        'mov_unit_cost_ref',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'mov_pro_code', 'pro_code');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'mov_war_id', 'id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'mov_invoice_number', 'invoice_number');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'mov_invoice_trans_type_code', 'trans_type_code');
    }

    public function getMovQuantityAttribute($value)
    {
        return (float) $value;
    }

    public function getMovUnitCostAttribute($value)
    {
        return (float) $value;
    }

    public function getMovFactorAttribute($value)
    {
        return (int) $value;
    }
}
