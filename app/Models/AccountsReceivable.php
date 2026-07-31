<?php

namespace App\Models;

use App\Enum\Sales\StatusSales;
use Database\Factories\AccountsReceivableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsReceivable extends Model
{
    /** @use HasFactory<AccountsReceivableFactory> */
    use HasFactory;

    protected $table = 'accounts_receivable';

    protected $fillable = [
        'acr_sls_number',
        'acr_total',
        'acr_balance',
        'acr_status',
        'acr_total_usd',
        'acr_balance_usd',
    ];

    public function saleOrder()
    {
        return $this->belongsTo(SaleOrder::class, 'acr_sls_number', 'sls_number');
    }

    public function payments()
    {
        return $this->hasMany(Receipt::class, 'rec_account_receivable_id');
    }

    public function bankTransactions()
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('acr_status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('acr_status', ['pending', 'partial']);
    }

    public function isPaid(): bool
    {
        return $this->acr_status === 'paid' || $this->acr_balance <= 0;
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->saleOrder?->customer?->clt_name ?? 'Desconocido';
    }

    public function registerPayment(float $amount, string $type, ?int $bankTransactionId = null, ?float $amountUsd = null): Receipt
    {
        $this->acr_balance -= $amount;

        if ($amountUsd !== null && $this->acr_balance_usd !== null) {
            $this->acr_balance_usd -= $amountUsd;
        }

        if ($this->acr_balance <= 0) {
            $this->acr_status = StatusSales::PAID->value;
            $this->acr_balance = 0;
            if ($this->acr_balance_usd !== null) {
                $this->acr_balance_usd = max(0, $this->acr_balance_usd);
            }
        } else {
            $this->acr_status = StatusSales::PARTIAL->value;
        }

        $this->save();

        if ($this->saleOrder) {
            $this->saleOrder->sls_status = $this->acr_status;
            $this->saleOrder->save();
        }

        return $this->payments()->create([
            'rec_bank_transaction_id' => $bankTransactionId,
            'rec_type' => $type,
            'rec_amount' => $amount,
        ]);
    }

    public function reversePayment(Receipt $receipt): void
    {
        $this->acr_balance += $receipt->rec_amount;

        if (isset($this->acr_balance_usd) && $receipt->bankTransaction) {
            $this->acr_balance_usd += (float) $receipt->bankTransaction->btr_diferencial;
            $paymentUsd = (float) $receipt->rec_amount;
            $payCoin = $receipt->bankTransaction->btr_coin?->value ?? $receipt->bankTransaction->btr_coin;
            if ($payCoin && strtoupper($payCoin) !== 'USD') {
                $paymentUsd = (float) $receipt->rec_amount;
                if ($receipt->bankTransaction->btr_valorcam > 0) {
                    $paymentUsd = match (strtoupper((string) $payCoin)) {
                        'COP' => (float) $receipt->rec_amount / (float) $receipt->bankTransaction->btr_valorcam,
                        'EUR' => (float) $receipt->rec_amount / (float) $receipt->bankTransaction->btr_valorcam,
                        default => (float) $receipt->rec_amount / (float) $receipt->bankTransaction->btr_valorcam,
                    };
                }
            }
            $this->acr_balance_usd += $paymentUsd;
        }

        if ($this->acr_balance >= $this->acr_total) {
            $this->acr_status = StatusSales::PENDING->value;
        } elseif ($this->acr_balance > 0) {
            $this->acr_status = StatusSales::PARTIAL->value;
        } else {
            $this->acr_status = StatusSales::PAID->value;
            $this->acr_balance = 0;
        }

        $this->save();

        if ($this->saleOrder) {
            $this->saleOrder->sls_status = $this->acr_status;
            $this->saleOrder->save();
        }
    }
}
