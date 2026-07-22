<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
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
        'invoice_status',
        'invoice_reference',
        'invoice_payment_condition',
        'invoice_credit_days',
        'invoice_due_date',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'invoice_due_date' => 'date',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'invoice_supplier_id', 'id');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'invoice_trans_type_code', 'trans_type_code');
    }

    public function movements()
    {
        return $this->hasMany(MovementsPurchase::class, 'mov_invoice_number', 'invoice_number');
    }

    /**
     * Relación con otras facturas que la referencian como origen (sus devoluciones)
     */
    public function childrenReturns()
    {
        return $this->hasMany(PurchaseOrder::class, 'invoice_reference', 'invoice_number');
    }

    public function returnedMovements()
    {
        return $this->hasManyThrough(
            MovementsPurchase::class,
            PurchaseOrder::class,
            'invoice_reference', // Foreign key on the intermediates table (purchase_orders for returns)
            'mov_invoice_number', // Foreign key on the target table (movements_purchases)
            'invoice_number', // Local key on the parent table (purchase_orders)
            'invoice_number' // Local key on the intermediate table
        );
    }

    public function accountsPayable()
    {
        return $this->hasOne(AccountsPayable::class, 'acp_purchase_number', 'invoice_number');
    }
}
