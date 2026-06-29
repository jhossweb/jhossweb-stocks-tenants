<?php

namespace App\Models;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected $fillable = [
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

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'exp_category_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'exp_bank_transaction_id');
    }

    public function payments()
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }

    public static function resolveRateForCoin(string $coin): float
    {
        $latestRate = Rate::orderBy('rate_date', 'desc')->first();

        return match ($coin) {
            'USD' => 1,
            'COP' => (float) ($latestRate?->rate_cop ?? 1),
            'EUR' => (float) ($latestRate?->rate_eur ?? 1),
            default => (float) ($latestRate?->rate_usd ?? 1),
        };
    }

    public function registerPayment(
        float $amountInBankCurrency,
        int $bankAccountId,
        string $reference,
        ?string $description = null,
        ?string $paymentCurrency = null,
        ?float $paymentRate = null,
        ?string $paymentMethod = null,
        ?string $paymentDate = null,
    ): BankTransaction {
        return DB::transaction(function () use ($amountInBankCurrency, $bankAccountId, $reference, $description, $paymentCurrency, $paymentRate, $paymentMethod, $paymentDate) {
            $bankAccount = BankAccount::findOrFail($bankAccountId);

            if ($paymentCurrency && $paymentRate !== null) {
                $rate = $paymentRate;
                $coin = $paymentCurrency;
            } else {
                $rate = $this->resolveRate($bankAccount);
                $coin = $bankAccount->bank_currency;
            }

            $amountInExpenseCurrency = $this->convertToExpenseCurrency($amountInBankCurrency, $rate, $coin);

            $bankTransaction = BankTransaction::create([
                'btr_bank_account_id' => $bankAccountId,
                'btr_type' => 'outflow',
                'btr_amount' => $amountInBankCurrency,
                'btr_balance' => 0,
                'btr_reference' => $reference,
                'btr_description' => $description ?: ('Pago de gasto: '.$this->exp_description),
                'btr_valorcam' => $rate,
                'btr_coin' => $coin,
                'btr_method' => $paymentMethod,
                'btr_transaction_date' => $paymentDate,
            ]);

            $bankTransaction->btr_transactionable()->associate($this);
            $bankTransaction->save();

            $this->exp_paid_amount += $amountInExpenseCurrency;
            $this->exp_remaining_balance = max(0, $this->exp_amount - $this->exp_paid_amount);

            if ($this->exp_remaining_balance <= 0) {
                $this->markAsPaid($bankTransaction->id);
            } else {
                $this->exp_bank_transaction_id = $bankTransaction->id;
                $this->save();
            }

            return $bankTransaction;
        });
    }

    public function resolveRate(BankAccount $bankAccount): float
    {
        if ($this->exp_coin === $bankAccount->bank_currency) {
            return 1;
        }

        return static::resolveRateForCoin($this->exp_coin->value);
    }

    public function convertToExpenseCurrency(float $amountInPaymentCurrency, float $paymentRate, string $paymentCoin): float
    {
        $expenseCoin = $this->exp_coin->value;

        if ($expenseCoin === $paymentCoin) {
            return $amountInPaymentCurrency;
        }

        $amountInUSD = $amountInPaymentCurrency / $paymentRate;
        $expenseRate = static::resolveRateForCoin($expenseCoin);

        return $amountInUSD * $expenseRate;
    }

    public function markAsPaid(?int $bankTransactionId = null): void
    {
        $this->exp_status = ExpenseStatus::PAID;
        $this->exp_payment_date = now();
        $this->exp_remaining_balance = 0;
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
