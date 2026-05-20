<?php

namespace App\Models;

use Database\Factories\AccountReceiptPaymentsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountReceiptPayments extends Model
{
    /** @use HasFactory<AccountReceiptPaymentsFactory> */
    use HasFactory;

    protected $fillable = [
        'arp_account_receivable_id',
        'arp_bank_transaction_id',
        'arp_type',
        'arp_amount',
    ];

    public function accountsReceivable()
    {
        return $this->belongsTo(AccountsReceivable::class, 'arp_account_receivable_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'arp_bank_transaction_id');
    }
}
