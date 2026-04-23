<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\BankTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        "btr_bank_account_id",
        "btr_type",
        "btr_amount",
        "btr_balance",
        "btr_reference",
        "btr_description",
        "btr_valorcam",
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, "btr_bank_account_id");
    }

    public function btr_transactionable()
    {
        return $this->morphTo();
    }
}
