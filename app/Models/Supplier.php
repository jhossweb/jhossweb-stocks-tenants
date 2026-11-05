<?php

namespace App\Models;

use App\Concerns\HasActiveStatus;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasActiveStatus, HasFactory;

    protected $fillable = [
        'is_active',
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
            'is_active' => 'boolean',
            'supplier_credit_days' => 'integer',
        ];
    }

    public function purchases()
    {
        return $this->hasMany(PurchaseOrder::class, 'invoice_supplier_id', 'id');
    }
}
