<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\BillingRate;
use App\Models\Rate;

/**
 * Conversión de monedas compartida entre el ERP (tabla por-tenant `rates`) y el
 * billing central (tabla `billing_rates`). Una sola implementación de la matriz
 * USD/COP/BS/VES/EUR; BS y VES se tratan como equivalentes (línea base histórica).
 */
final class CurrencyConverterService
{
    public function convert(float $amount, string $from, string $to, Rate|BillingRate $rate): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to || $amount === 0) {
            return $amount;
        }

        $usd = (float) ($rate->rate_usd ?: 1);
        $cop = (float) ($rate->rate_cop ?: 1);
        $eur = (float) ($rate->rate_eur ?: 1);
        $copBs = (float) ($rate->rate_cop_bs ?: 0);

        return match (true) {
            // USD →
            $from === 'USD' && $to === 'COP' => $amount * $cop,
            $from === 'USD' && in_array($to, ['BS', 'VES'], true) => $amount * $usd,
            $from === 'USD' && $to === 'EUR' => $amount * $eur,
            // COP →
            $from === 'COP' && $to === 'USD' => $amount / $cop,
            $from === 'COP' && in_array($to, ['BS', 'VES'], true) => $copBs > 0 ? $amount / $copBs : $amount * $usd / $cop,
            $from === 'COP' && $to === 'EUR' => $amount / $cop * $eur,
            // BS/VES →
            in_array($from, ['BS', 'VES'], true) && $to === 'USD' => $amount / $usd,
            in_array($from, ['BS', 'VES'], true) && $to === 'COP' => $copBs > 0 ? $amount * $copBs : $amount * $cop / $usd,
            in_array($from, ['BS', 'VES'], true) && $to === 'EUR' => $amount / $usd * $eur,
            // EUR →
            $from === 'EUR' && $to === 'USD' => $amount / $eur,
            $from === 'EUR' && $to === 'COP' => $amount / $eur * $cop,
            $from === 'EUR' && in_array($to, ['BS', 'VES'], true) => $amount / $eur * $usd,
            // Fallback
            default => $amount,
        };
    }

    public function latestBillingRate(): ?BillingRate
    {
        return BillingRate::latestRate();
    }

    public function rateForDate(\DateTimeInterface $date): ?BillingRate
    {
        return BillingRate::forDate($date);
    }
}
