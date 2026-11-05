<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasDocumentLifecycle;
    use HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $table = 'receipts';

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'rec_account_receivable_id',
        'rec_bank_transaction_id',
        'rec_type',
        'rec_amount',
        'rec_diferencial',
    ];

    public function accountsReceivable()
    {
        return $this->belongsTo(AccountsReceivable::class, 'rec_account_receivable_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'rec_bank_transaction_id');
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }
}
