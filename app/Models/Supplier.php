<?php

namespace App\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'supplier_email',
        'supplier_website',
        'supplier_logo',
        'supplier_credit_days',
    ];

    protected function casts(): array
    {
        return [
            'supplier_credit_days' => 'integer',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(PurchaseOrder::class, 'invoice_supplier_id', 'id');
    }
}
