<?php

namespace App\Models;

use App\Concerns\HasActiveStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasActiveStatus, HasFactory;

    protected $fillable = [
        'is_active',
        'clt_code',
        'clt_cedula',
        'clt_name',
        'clt_address',
        'clt_phone',
        'clt_email',
        'clt_website',
        'clt_logo',
        'clt_credit_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'clt_credit_days' => 'integer',
        ];
    }

    public function sales()
    {
        return $this->hasMany(SaleOrder::class, 'sls_customer_id', 'id');
    }

    public function accountsReceivable(): HasManyThrough
    {
        return $this->hasManyThrough(
            AccountsReceivable::class,
            SaleOrder::class,
            'sls_customer_id',
            'acr_sale_order_id',
            'id', // Clave local en la tabla customers
            'id' // clave local en la tabla sale_orders
        );
    }
}
