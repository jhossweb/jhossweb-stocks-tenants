<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'clt_name',
        'clt_address',
        'clt_phone',
        'clt_email',
        'clt_website',
        'clt_logo',
    ];

    function sales()
    {
        return $this->hasMany(SaleOrder::class, 'sls_customer_id', 'id');
    }
}
