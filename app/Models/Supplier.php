<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'supplier_email',
        'supplier_website',
        'supplier_logo',
    ];

    function purchases()
    {
        return $this->hasMany(PurchaseOrder::class, 'invoice_supplier_id', 'id');
    }
}
