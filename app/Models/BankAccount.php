<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\BankAccountFactory> */
    use HasFactory;
    protected $fillable = [
        "bank_name",
        "bank_account_number",
        "bank_type",
        "bank_currency",
        "bank_status",
        "bank_description",
        "bank_balance",
    ];

    public function bankTransactions()
    {
        return $this->hasMany(BankTransaction::class, "btr_bank_account_id");
    }
}
