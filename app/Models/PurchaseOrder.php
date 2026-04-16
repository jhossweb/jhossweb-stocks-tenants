<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'invoice_trans_type_code',
        'invoice_supplier_id',
        'invoice_valorcam',
        'invoice_coin',
        'invoice_subtotal',
        'invoice_exento',
        'invoice_iva',
        'invoice_total',
        'invoice_reference',
    ];

    function supplier()
    {
        return $this->belongsTo(Supplier::class, 'invoice_supplier_id', 'id');
    }

    function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'invoice_trans_type_code', 'trans_type_code');
    }

    function movements()
    {
        return $this->hasMany(MovementsPurchase::class, 'mov_invoice_number', 'invoice_number');
    }
}
