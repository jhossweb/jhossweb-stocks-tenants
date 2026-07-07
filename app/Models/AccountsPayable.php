<?php

namespace App\Models;

use App\Enum\Purchases\StatusPurchases;
use Database\Factories\AccountsPayableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountsPayable extends Model
{
    /** @use HasFactory<AccountsPayableFactory> */
    use HasFactory;

    protected $fillable = [
        'acp_purchase_number',
        'acp_total',
        'acp_balance',
        'acp_status',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'acp_purchase_number', 'invoice_number');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('acp_status', $status);
    }

    /**
     * Scope to filter pending or partial accounts
     */
    public function scopePending($query)
    {
        return $query->whereIn('acp_status', ['PENDIENTE', 'PARCIAL']);
    }

    /**
     * Check if the account is fully paid
     */
    public function isPaid(): bool
    {
        return $this->acp_status === 'paid' || $this->acp_balance <= 0;
    }

    /**
     * Shortcut to get the supplier name
     */
    public function getSupplierNameAttribute(): string
    {
        return $this->purchaseOrder?->supplier?->supplier_name ?? 'Desconocido';
    }

    /**
     * Register a payment for this account
     */
    public function registerPayment(float $amount, string $type, ?int $bankTransactionId = null, ?string $invoiceReference = null): void
    {
        $this->acp_balance -= $amount;

        if ($this->acp_balance <= 0) {
            $this->acp_status = StatusPurchases::PAID->value;
        } else {
            $this->acp_status = StatusPurchases::PARTIAL->value;
        }

        $this->save();

        // Sincronizar el estado con el documento original (PurchaseOrder)
        if ($this->purchaseOrder) {
            $this->purchaseOrder->invoice_status = $this->acp_status;
            $this->purchaseOrder->save();
        }

        $this->payments()->create([
            'apy_bank_transaction_id' => $bankTransactionId,
            'apy_type' => $type,
            'apy_amount' => $amount,
            'apy_invoice_reference' => $invoiceReference,
        ]);
    }

    /**
     * Reverse a payment/credit and restore the account balance
     */
    public function reversePayment(AccountPayments $payment): void
    {
        $this->acp_balance += $payment->apy_amount;

        $this->acp_status = match (true) {
            $this->acp_balance <= 0 => StatusPurchases::PAID->value,
            $this->acp_balance < $this->acp_total => StatusPurchases::PARTIAL->value,
            default => StatusPurchases::PENDING->value,
        };

        $this->save();

        if ($this->purchaseOrder) {
            $this->purchaseOrder->invoice_status = $this->acp_status;
            $this->purchaseOrder->save();
        }

        $payment->delete();
    }

    public function payments()
    {
        return $this->hasMany(AccountPayments::class, 'apy_account_payable_id');
    }

    public function bankTransactions()
    {
        return $this->morphMany(BankTransaction::class, 'btr_transactionable');
    }
}
