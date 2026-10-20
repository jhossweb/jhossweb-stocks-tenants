<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enum\Tenancy\PaymentStatus;
use App\Enum\Tenancy\TenantEventType;
use App\Models\SubscriptionPayment;
use App\Models\TenantEvent;
use Illuminate\Support\Facades\Auth;

final class VoidPaymentAction
{
    /**
     * Anula lógicamente un cobro (no se borra) para conservar la auditoría.
     */
    public function execute(SubscriptionPayment $payment, ?string $reason = null, ?int $userId = null): SubscriptionPayment
    {
        if ($payment->isVoided()) {
            return $payment;
        }

        $userId ??= Auth::id();

        $payment->status = PaymentStatus::VOIDED->value;
        $payment->voided_at = now();
        $payment->voided_by = $userId;

        if ($reason !== null && $reason !== '') {
            $payment->notes = trim(($payment->notes ? $payment->notes.' ' : '')."[Anulado] {$reason}");
        }

        $payment->save();

        TenantEvent::create([
            'tenant_id' => $payment->tenant_id,
            'user_id' => $userId,
            'event' => TenantEventType::PAYMENT_VOIDED->value,
            'description' => "Cobro {$payment->receipt_number} anulado",
            'meta' => ['payment_id' => $payment->id, 'reason' => $reason],
        ]);

        return $payment->refresh();
    }
}
