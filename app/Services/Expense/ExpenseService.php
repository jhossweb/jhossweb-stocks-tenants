<?php

namespace App\Services\Expense;

use App\Enum\Expense\ExpenseStatus;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Services\Finance\RateService;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        protected RateService $rateService,
    ) {}

    public function create(array $data): Expense
    {
        $valorcam = $data['exp_valorcam'] ?? $this->rateService->resolveRateForCoin(
            $data['exp_date'],
            $data['exp_coin'],
        );

        return Expense::create([
            'exp_category_id' => $data['exp_category_id'],
            'exp_description' => $data['exp_description'],
            'exp_amount' => $data['exp_amount'],
            'exp_paid_amount' => 0,
            'exp_remaining_balance' => $data['exp_amount'],
            'exp_date' => $data['exp_date'],
            'exp_status' => ExpenseStatus::PENDING,
            'exp_coin' => $data['exp_coin'],
            'exp_valorcam' => $valorcam,
        ]);
    }

    public function update(Expense $expense, array $data): Expense
    {
        $newAmount = (float) $data['exp_amount'];
        $paidAmount = (float) $expense->exp_paid_amount;

        if ($newAmount < $paidAmount) {
            throw new \InvalidArgumentException(
                "El monto del gasto ({$newAmount}) no puede ser menor al ya pagado ({$paidAmount})."
            );
        }

        $data['exp_remaining_balance'] = $newAmount - $paidAmount;

        if ($data['exp_remaining_balance'] <= 0) {
            $data['exp_status'] = ExpenseStatus::PAID;
        } elseif ($paidAmount > 0) {
            $data['exp_status'] = ExpenseStatus::PARTIAL ?? $expense->exp_status;
        }

        $valorcam = $this->rateService->resolveRateForCoin(
            $data['exp_date'] ?? $expense->exp_date->format('Y-m-d'),
            $data['exp_coin'] ?? $expense->exp_coin->value,
        );
        $data['exp_valorcam'] = $valorcam;

        $expense->update($data);

        return $expense->fresh();
    }

    public function registerPayment(
        Expense $expense,
        float $amountInBankCurrency,
        int $bankAccountId,
        string $reference,
        ?string $description = null,
        ?string $paymentCurrency = null,
        ?float $paymentRate = null,
        ?string $paymentMethod = null,
        ?string $paymentDate = null,
    ): BankTransaction {
        return DB::transaction(function () use (
            $expense, $amountInBankCurrency, $bankAccountId, $reference,
            $description, $paymentCurrency, $paymentRate, $paymentMethod, $paymentDate
        ) {
            $bankAccount = BankAccount::findOrFail($bankAccountId);

            $payCoin = $paymentCurrency ?? $bankAccount->bank_currency;
            $payRate = $paymentRate ?? $this->resolvePayRate($expense, $bankAccount);

            $amountInExpenseCurrency = $this->convertToExpenseCurrency(
                $expense, $amountInBankCurrency, $payRate, $payCoin
            );

            $diferencial = $this->calculateDiferencial(
                $expense, $amountInBankCurrency, $payRate, $payCoin
            );

            $bankTransaction = BankTransaction::create([
                'btr_bank_account_id' => $bankAccountId,
                'btr_type' => 'outflow',
                'btr_amount' => $amountInBankCurrency,
                'btr_balance' => 0,
                'btr_reference' => $reference,
                'btr_description' => $description ?: ('Pago de gasto: '.$expense->exp_description),
                'btr_valorcam' => $payRate,
                'btr_diferencial' => $diferencial,
                'btr_coin' => $payCoin,
                'btr_method' => $paymentMethod,
                'btr_transaction_date' => $paymentDate,
            ]);

            $bankTransaction->btr_transactionable()->associate($expense);
            $bankTransaction->save();

            $expense->exp_paid_amount = (float) $expense->exp_paid_amount + $amountInExpenseCurrency;
            $expense->exp_remaining_balance = max(0, (float) $expense->exp_amount - $expense->exp_paid_amount);

            if ($expense->exp_remaining_balance <= 0) {
                $expense->exp_status = ExpenseStatus::PAID;
                $expense->exp_payment_date = $paymentDate ? now()->parse($paymentDate) : now();
                $expense->exp_remaining_balance = 0;
            } else {
                $expense->exp_status = ExpenseStatus::PENDING;
            }

            $expense->exp_bank_transaction_id = $bankTransaction->id;
            $expense->save();

            return $bankTransaction;
        });
    }

    public function cancelPayment(Expense $expense, int $bankTransactionId): void
    {
        DB::transaction(function () use ($expense, $bankTransactionId) {
            $payment = BankTransaction::where('id', $bankTransactionId)
                ->where('btr_transactionable_id', $expense->id)
                ->where('btr_transactionable_type', Expense::class)
                ->firstOrFail();

            $paidInExpenseCurrency = $this->convertToExpenseCurrency(
                $expense,
                (float) $payment->btr_amount,
                (float) $payment->btr_valorcam,
                $payment->btr_coin->value ?? (string) $payment->btr_coin,
                (float) $expense->exp_valorcam,
            );

            $expense->exp_paid_amount = max(0, (float) $expense->exp_paid_amount - $paidInExpenseCurrency);
            $expense->exp_remaining_balance = (float) $expense->exp_amount - $expense->exp_paid_amount;

            if ((float) $expense->exp_paid_amount <= 0) {
                $expense->exp_status = ExpenseStatus::PENDING;
                $expense->exp_payment_date = null;
                $expense->exp_bank_transaction_id = null;
            } else {
                $expense->exp_status = ExpenseStatus::PENDING;
            }

            $expense->save();

            $payment->delete();
        });
    }

    public function delete(Expense $expense): void
    {
        if ($expense->isPaid()) {
            throw new \LogicException('No se puede eliminar un gasto pagado.');
        }

        DB::transaction(function () use ($expense) {
            BankTransaction::where('btr_transactionable_id', $expense->id)
                ->where('btr_transactionable_type', Expense::class)
                ->delete();

            $expense->delete();
        });
    }

    private function resolvePayRate(Expense $expense, BankAccount $bankAccount): float
    {
        return $this->rateService->resolveRateForCoin(
            now()->toDateString(),
            $bankAccount->bank_currency,
        );
    }

    private function convertToExpenseCurrency(
        Expense $expense,
        float $amountInPaymentCurrency,
        float $paymentRate,
        string $paymentCoin,
        ?float $expenseRate = null,
    ): float {
        $expenseCoin = $expense->exp_coin->value ?? (string) $expense->exp_coin;
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

        if ($payUpper === 'USD') {
            $amountInUSD = $amountInPaymentCurrency;
        } else {
            $amountInUSD = $paymentRate > 0 ? $amountInPaymentCurrency / $paymentRate : 0;
        }

        if ($expenseUpper === 'USD') {
            return $amountInUSD;
        }

        $rate = $expenseRate ?? $this->getLatestCachedRate($expenseCoin);

        return $rate > 0 ? $amountInUSD * $rate : 0;
    }

    private function calculateDiferencial(
        Expense $expense,
        float $amountInBankCurrency,
        float $payRate,
        string $payCoin,
    ): float {
        $expenseCoin = $expense->exp_coin->value ?? (string) $expense->exp_coin;
        $payUpper = strtoupper($payCoin);
        $expenseUpper = strtoupper($expenseCoin);

        if ($payUpper === $expenseUpper) {
            return 0.0;
        }

        $docRate = (float) $expense->exp_valorcam;

        if ($docRate <= 0 || $payRate <= 0) {
            return 0.0;
        }

        if (in_array($payUpper, ['COP', 'BS', 'VES'], true) && in_array($expenseUpper, ['COP', 'BS', 'VES'], true)) {
            $amountInExpenseCurrency = $payUpper === 'COP'
                ? ($payRate > 0 ? $amountInBankCurrency / $payRate : 0)
                : $amountInBankCurrency * $payRate;

            $latestExpenseRate = $this->getLatestCachedRate($expenseCoin);

            $payInUSD = $latestExpenseRate > 0 ? $amountInExpenseCurrency / $latestExpenseRate : 0;
            $docInUSD = $docRate > 0 ? $amountInExpenseCurrency / $docRate : 0;
        } else {
            $payInUSD = $payUpper === 'USD'
                ? $amountInBankCurrency
                : ($payRate > 0 ? $amountInBankCurrency / $payRate : 0);

            $docInUSD = $payUpper === 'USD'
                ? $amountInBankCurrency
                : ($docRate > 0 ? $amountInBankCurrency / $docRate : 0);
        }

        return round($docInUSD - $payInUSD, 4);
    }

    private function getLatestCachedRate(string $coin): float
    {
        $latestRate = $this->rateService->getLatestRate();

        if (! $latestRate) {
            return 1;
        }

        return match (strtoupper($coin)) {
            'USD' => 1,
            'COP' => (float) $latestRate->rate_cop,
            'EUR' => (float) $latestRate->rate_eur,
            default => (float) $latestRate->rate_usd,
        };
    }
}
