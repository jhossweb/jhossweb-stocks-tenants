<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPayments extends Model
{
    /** @use HasFactory<\Database\Factories\AccountPaymentsFactory> */
    use HasFactory;

    protected $fillable = [
        "apy_bank_transaction_id",
        "apy_account_payable_id",
        "apy_type",
        "apy_amount",
    ];

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, "apy_bank_transaction_id");
    }

    public function accountPayable()
    {
        return $this->belongsTo(AccountsPayable::class, "apy_account_payable_id");
    }
}
