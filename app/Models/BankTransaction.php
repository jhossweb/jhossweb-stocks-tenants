<?php

namespace App\Models;

use App\Enum\TypeCoin;
use Database\Factories\BankTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    /** @use HasFactory<BankTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'btr_bank_account_id',
        'btr_type',
        'btr_amount',
        'btr_balance',
        'btr_reference',
        'btr_description',
        'btr_valorcam',
        'btr_coin',
    ];

    protected function casts(): array
    {
        return [
            'btr_coin' => TypeCoin::class,
        ];
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'btr_bank_account_id');
    }

    public function btr_transactionable()
    {
        return $this->morphTo();
    }
}
