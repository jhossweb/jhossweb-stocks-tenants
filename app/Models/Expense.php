<?php

namespace App\Models;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'exp_category_id',
        'exp_description',
        'exp_amount',
        'exp_date',
        'exp_status',
        'exp_coin',
        'exp_valorcam',
        'exp_payment_date',
        'exp_bank_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'exp_date' => 'date',
            'exp_payment_date' => 'date',
            'exp_amount' => 'decimal:2',
            'exp_valorcam' => 'decimal:4',
            'exp_status' => ExpenseStatus::class,
            'exp_coin' => TypeCoin::class,
        ];
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'exp_category_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'exp_bank_transaction_id');
    }

    public function markAsPaid(?int $bankTransactionId = null): void
    {
        $this->exp_status = ExpenseStatus::PAID;
        $this->exp_payment_date = now();
        if ($bankTransactionId) {
            $this->exp_bank_transaction_id = $bankTransactionId;
        }
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->exp_status === ExpenseStatus::PAID;
    }
}
