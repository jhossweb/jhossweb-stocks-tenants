<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Tenancy\PaymentStatus;
use Database\Factories\SubscriptionPaymentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cobro manual de una suscripción del SaaS.
 *
 * Cada cobro congela la tasa de cambio (`exchange_rate`) y el equivalente en
 * USD (`amount_usd`) al momento de registrarse, para que la emisión de un
 * recibo sea inmutable frente a cambios posteriores de tasa.
 *
 * @method static SubscriptionPaymentFactory factory()
 */
class SubscriptionPayment extends Model
{
    /** @use HasFactory<SubscriptionPaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'tenant_id',
        'receipt_number',
        'amount',
        'currency',
        'exchange_rate',
        'amount_usd',
        'method',
        'reference',
        'paid_at',
        'status',
        'recorded_by',
        'voided_at',
        'voided_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'amount_usd' => 'decimal:2',
            'paid_at' => 'date',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * Congela el equivalente en USD al momento de registrar el cobro.
     */
    protected static function booted(): void
    {
        static::creating(function (self $payment): void {
            if ($payment->amount_usd === null && $payment->exchange_rate !== null) {
                $rate = (float) $payment->exchange_rate;

                $payment->amount_usd = $rate > 0
                    ? round((float) $payment->amount / $rate, 2)
                    : (float) $payment->amount;
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * @param  Builder<SubscriptionPayment>  $query
     * @return Builder<SubscriptionPayment>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PAID->value);
    }

    public function isVoided(): bool
    {
        return $this->status === PaymentStatus::VOIDED->value;
    }
}
