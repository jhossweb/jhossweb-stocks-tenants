<?php

namespace App\Models;

use Database\Factories\AccountPaymentsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPayments extends Model
{
    /** @use HasFactory<AccountPaymentsFactory> */
    use HasFactory;

    protected $fillable = [
        'apy_bank_transaction_id',
        'apy_account_payable_id',
        'apy_type',
        'apy_amount',
        'apy_invoice_reference',
    ];

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'apy_bank_transaction_id');
    }

    public function accountPayable()
    {
        return $this->belongsTo(AccountsPayable::class, 'apy_account_payable_id');
    }
}
