<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'clt_name',
        'clt_address',
        'clt_phone',
        'clt_email',
        'clt_website',
        'clt_logo',
    ];

    public function sales()
    {
        return $this->hasMany(SaleOrder::class, 'sls_customer_id', 'id');
    }
}
