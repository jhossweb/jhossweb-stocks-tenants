<?php

namespace App\Models;

use App\Concerns\HasDocumentLifecycle;
use App\Concerns\HasVoidability;
use Database\Factories\SaleOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleOrder extends Model
{
    /** @use HasFactory<SaleOrderFactory> */
    use HasDocumentLifecycle, HasFactory, HasVoidability;

    protected $appends = ['can_void', 'void_blocked_reason'];

    protected $fillable = [
        'is_active',
        'voided_at',
        'voided_by',
        'void_reason',
        'sls_number',
        'sls_date',
        'sls_trans_type_code',
        'sls_customer_id',
        'sls_valorcam',
        'sls_coin',
        'sls_subtotal',
        'sls_exento',
        'sls_iva',
        'sls_total',
        'sls_status',
        'sls_reference',
        'sls_payment_condition',
        'sls_credit_days',
        'sls_due_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'voided_at' => 'datetime',
            'sls_date' => 'date',
            'sls_due_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'sls_customer_id', 'id');
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'sls_trans_type_code', 'trans_type_code');
    }

    public function movements()
    {
        return $this->hasMany(MovementsSale::class, 'mvs_number', 'sls_number');
    }

    public function childrenReturns()
    {
        return $this->hasMany(SaleOrder::class, 'sls_reference', 'sls_number');
    }

    public function accountsReceivable()
    {
        return $this->hasOne(AccountsReceivable::class, 'acr_sls_number', 'sls_number');
    }

    public function returnedMovements()
    {
        return $this->hasManyThrough(MovementsSale::class, SaleOrder::class, 'sls_reference', 'mvs_number', 'sls_number', 'mvs_number');
    }

    public function getDaysRemainingAttribute(): int
    {
        $due = $this->sls_due_date;

        if (! $due) {
            $due = $this->sls_date?->copy()->addDays((int) $this->sls_credit_days);
        }

        if (! $due) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($due->startOfDay(), false);
    }
}
