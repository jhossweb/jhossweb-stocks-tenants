<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use Database\Factories\PayrollFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payroll extends Model
{
    /** @use HasFactory<PayrollFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'pay_employee_id',
        'pay_period_id',
        'pay_base_salary',
        'pay_coin',
        'pay_valorcam',
        'pay_total_deductions',
        'pay_net_salary',
        'pay_status',
        'pay_bank_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
            'pay_base_salary' => 'decimal:2',
            'pay_valorcam' => 'decimal:4',
            'pay_total_deductions' => 'decimal:2',
            'pay_net_salary' => 'decimal:2',
            'pay_status' => PayrollStatus::class,
            'pay_coin' => TypeCoin::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'pay_employee_id');
    }

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'pay_period_id');
    }

    public function deductions()
    {
        return $this->hasMany(PayrollDeduction::class, 'pde_payroll_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'pay_bank_transaction_id');
    }

    public function payments()
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }

    public function recalculate(): void
    {
        $this->pay_total_deductions = $this->deductions()->sum('pde_amount');
        $this->pay_net_salary = $this->pay_base_salary - $this->pay_total_deductions;
        $this->save();
    }

    public function markAsPaid(?int $bankTransactionId = null): void
    {
        $this->pay_status = PayrollStatus::PAID;
        if ($bankTransactionId) {
            $this->pay_bank_transaction_id = $bankTransactionId;
        }
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->pay_status === PayrollStatus::PAID;
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

            $bankTransaction = BankTransaction::create([
                'btr_bank_account_id' => $bankAccountId,
                'btr_type' => 'outflow',
                'btr_amount' => $amountInBankCurrency,
                'btr_balance' => 0,
                'btr_reference' => $reference,
                'btr_description' => $description ?: ('Pago de nómina: '.($this->employee?->emp_name ?? '')),
                'btr_valorcam' => $rate,
                'btr_coin' => $coin,
                'btr_method' => $paymentMethod,
                'btr_transaction_date' => $paymentDate,
            ]);

            $bankTransaction->btr_transactionable()->associate($this);
            $bankTransaction->save();

            $this->markAsPaid($bankTransaction->id);

            return $bankTransaction;
        });
    }

    public function resolveRate(BankAccount $bankAccount): float
    {
        if ($this->pay_coin === $bankAccount->bank_currency) {
            return 1;
        }

        $latestRate = Rate::orderBy('rate_date', 'desc')->first();

        return match ($this->pay_coin) {
            TypeCoin::USD => (float) ($latestRate?->rate_usd ?? 1),
            TypeCoin::COP => (float) ($latestRate?->rate_cop ?? 1),
            TypeCoin::EUR => (float) ($latestRate?->rate_eur ?? 1),
            default => (float) ($latestRate?->rate_usd ?? 1),
        };
    }

    public function registerDeduction(string $deductibleType, int $deductibleId, float $percentage): PayrollDeduction
    {
        $amount = $this->pay_base_salary * ($percentage / 100);

        $deduction = $this->deductions()->create([
            'pde_deductible_type' => $deductibleType,
            'pde_deductible_id' => $deductibleId,
            'pde_percentage' => $percentage,
            'pde_amount' => $amount,
        ]);

        // Reduce balance on the loan or damage
        $deductible = $deduction->deductible;
        if ($deductible) {
            $deductible->reduceBalance($amount);
        }

        $this->recalculate();

        return $deduction;
    }
}
