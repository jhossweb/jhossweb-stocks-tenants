<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $table = 'receipts';

    protected $fillable = [
        'rec_account_receivable_id',
        'rec_bank_transaction_id',
        'rec_type',
        'rec_amount',
    ];

    public function accountsReceivable()
    {
        return $this->belongsTo(AccountsReceivable::class, 'rec_account_receivable_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'rec_bank_transaction_id');
    }
}
