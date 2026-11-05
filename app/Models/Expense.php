<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'exp_category_id',
        'exp_description',
        'exp_amount',
        'exp_paid_amount',
        'exp_remaining_balance',
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
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
            'exp_date' => 'date',
            'exp_payment_date' => 'date',
            'exp_amount' => 'decimal:2',
            'exp_paid_amount' => 'decimal:2',
            'exp_remaining_balance' => 'decimal:2',
            'exp_valorcam' => 'decimal:4',
            'exp_status' => ExpenseStatus::class,
            'exp_coin' => TypeCoin::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'exp_category_id');
    }

    public function bankTransaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class, 'exp_bank_transaction_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('exp_status', ExpenseStatus::PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('exp_status', ExpenseStatus::PAID);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('exp_category_id', $categoryId);
    }

    public function isPaid(): bool
    {
        return $this->exp_status === ExpenseStatus::PAID;
    }

    public function isPending(): bool
    {
        return $this->exp_status === ExpenseStatus::PENDING;
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->exp_paid_amount;
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->exp_remaining_balance;
    }

    public function convertToExpenseCurrency(float $amountInPaymentCurrency, float $paymentRate, string $paymentCoin): float
    {
        $expenseCoin = $this->exp_coin->value ?? (string) $this->exp_coin;
        $payUpper = strtoupper($paymentCoin);
        $expenseUpper = strtoupper($expenseCoin);

        if ($expenseUpper === $payUpper) {
            return $amountInPaymentCurrency;
        }

        if (in_array($payUpper, ['COP', 'BS', 'VES'], true) && in_array($expenseUpper, ['COP', 'BS', 'VES'], true)) {
            if ($payUpper === 'COP' && in_array($expenseUpper, ['BS', 'VES'], true)) {
                return $paymentRate > 0 ? $amountInPaymentCurrency / $paymentRate : 0;
            }

            if (in_array($payUpper, ['BS', 'VES'], true) && $expenseUpper === 'COP') {
                return $amountInPaymentCurrency * $paymentRate;
            }

            return $amountInPaymentCurrency;
        }

        $amountInUSD = $paymentRate > 0 ? $amountInPaymentCurrency / $paymentRate : 0;

        return match ($expenseUpper) {
            'USD' => $amountInUSD,
            default => $amountInUSD * (float) ($this->exp_valorcam ?: 1),
        };
    }
}
