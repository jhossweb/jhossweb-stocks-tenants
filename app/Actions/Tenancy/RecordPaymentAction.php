<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enum\Tenancy\Currency;
use App\Enum\Tenancy\PaymentStatus;
use App\Enum\Tenancy\TenantEventType;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\TenantEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecordPaymentAction
{
    /**
     * Registra un cobro manual de una suscripción, congelando la tasa de cambio
     * y generando el número de recibo correlativo (`REC-0001`).
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(Subscription $subscription, array $data, ?int $userId = null): SubscriptionPayment
    {
        return DB::transaction(function () use ($subscription, $data, $userId): SubscriptionPayment {
            $currency = (string) ($data['currency'] ?? Currency::USD->value);
            $exchangeRate = (float) ($data['exchange_rate'] ?? 1);

            $payment = new SubscriptionPayment([
                'subscription_id' => $subscription->id,
                'tenant_id' => $subscription->tenant_id,
                'receipt_number' => $this->nextReceiptNumber(),
                'amount' => (float) $data['amount'],
                'currency' => $currency,
                'exchange_rate' => $exchangeRate > 0 ? $exchangeRate : 1,
                'method' => (string) ($data['method'] ?? 'transfer'),
                'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'status' => PaymentStatus::PAID->value,
                'recorded_by' => $userId ?? Auth::id(),
                'notes' => $data['notes'] ?? null,
            ]);
            $payment->save();

            TenantEvent::create([
                'tenant_id' => $subscription->tenant_id,
                'user_id' => $userId ?? Auth::id(),
                'event' => TenantEventType::PAYMENT_REGISTERED->value,
                'description' => "Cobro {$payment->receipt_number} registrado",
                'meta' => [
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $payment->amount,
                    'currency' => $currency,
                ],
            ]);

            return $payment;
        });
    }

    private function nextReceiptNumber(): string
    {
        $last = SubscriptionPayment::query()
            ->whereNotNull('receipt_number')
            ->orderByDesc('id')
            ->value('receipt_number');

        $number = $last ? ((int) Str::after((string) $last, '-')) + 1 : 1;

        return 'REC-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
