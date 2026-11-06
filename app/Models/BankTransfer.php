<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankTransfer extends Model
{
    protected $fillable = [
        'bt_number',
        'bt_date',
        'bt_source_bank_account_id',
        'bt_destination_bank_account_id',
        'bt_amount',
        'bt_coin',
        'bt_reference',
        'bt_description',
        'bt_created_by',
        'bt_voided_at',
        'bt_voided_by',
        'bt_void_reason',
    ];

    protected function casts(): array
    {
        return [
            'bt_date' => 'date',
            'bt_amount' => 'decimal:4',
            'bt_voided_at' => 'datetime',
        ];
    }

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bt_source_bank_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bt_destination_bank_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bt_created_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bt_voided_by');
    }

    /**
     * @return MorphMany<BankTransaction, $this>
     */
    public function transactions(): MorphMany
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }

    public function isVoided(): bool
    {
        return $this->bt_voided_at !== null;
    }
}
