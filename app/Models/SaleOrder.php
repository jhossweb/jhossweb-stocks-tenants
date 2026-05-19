<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    /** @use HasFactory<\Database\Factories\SaleOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'sls_number',
        'sls_date',
        'sls_trans_type_code',
        'sls_customer_id',
        'sls_valorcam',
        'sls_coin',
        'sls_subtotal',
        'sls_exento',
        'sls_iva',
        'sls_total',
        'sls_status',
        'sls_reference',
    ];

    function customer()
    {
        return $this->belongsTo(Customer::class, 'sls_customer_id', 'id');
    }

    function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'sls_trans_type_code', 'trans_type_code');
    }

    function movements()
    {
        return $this->hasMany(MovementsSale::class, 'mvs_number', 'sls_number');
    }

    public function childrenReturns()
    {
        return $this->hasMany(SaleOrder::class, 'sls_reference', 'sls_number');
    }

    public function accountsReceivable()
    {
        return $this->hasOne(AccountsReceivable::class, 'acr_sls_number', 'sls_number');
    }

    public function returnedMovements()
    {
        return $this->hasManyThrough(MovementsSale::class, SaleOrder::class, 'sls_reference', 'mvs_number', 'sls_number', 'mvs_number');
    }
}
