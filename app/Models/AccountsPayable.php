<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsPayable extends Model
{
    /** @use HasFactory<\Database\Factories\AccountsPayableFactory> */
    use HasFactory;

    protected $fillable = [
        'acp_purchase_number',
        'acp_total',
        'acp_balance',
        'acp_status',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'acp_purchase_number', 'invoice_number');
    }

    public function payments()
    {
        return $this->hasMany(AccountPayments::class, 'apy_account_payable_id');
    }
}
