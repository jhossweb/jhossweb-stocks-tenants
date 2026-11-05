<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use Database\Factories\AccountPaymentsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPayments extends Model
{
    /** @use HasFactory<AccountPaymentsFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'apy_bank_transaction_id',
        'apy_account_payable_id',
        'apy_type',
        'apy_amount',
        'apy_diferencial',
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

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }
}
