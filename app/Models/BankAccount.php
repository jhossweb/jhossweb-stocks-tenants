<?php

namespace App\Models;

use Database\Factories\BankAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    /** @use HasFactory<BankAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'bank_account_number',
        'bank_type',
        'bank_currency',
        'bank_status',
        'bank_description',
        'bank_balance',
    ];

    /**
     * Scope to filter active bank accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('bank_status', true);
    }

    /**
     * Formatted balance with currency.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->bank_balance, 2).' '.$this->bank_currency;
    }

    public function bankTransactions()
    {
        return $this->hasMany(BankTransaction::class, 'btr_bank_account_id');
    }
}
