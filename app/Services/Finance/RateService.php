<?php

namespace App\Services\Finance;

use App\Concerns\ScopesCacheToTenant;
use App\DTOs\Finance\Rate\RateNotice;
use App\Enum\Finanzas\RateSeverity;
use App\Models\Rate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RateService
{
    use ScopesCacheToTenant;

    private const CACHE_KEY = 'rates:v2';

    private const CACHE_TTL = 3600;

    /**
     * Obtiene la tasa más reciente (con caché de 1 hora).
     * Cachea un array plano para evitar __PHP_Incomplete_Class con driver database.
     */
    public function getLatestRate(): ?Rate
    {
        $cached = Cache::remember(self::tenantCacheKey(self::CACHE_KEY), self::CACHE_TTL, function () {
            $rate = Rate::orderBy('rate_date', 'desc')->first();

            return $rate?->toArray();
        });

        if (! is_array($cached)) {
            return Rate::orderBy('rate_date', 'desc')->first();
        }

        return (new Rate)->forceFill($cached);
    }

    /**
     * Aviso de tasa ausente o vencida, o null si la tasa está al día.
     *
     * No cuesta una query extra: si hay tasa para hoy, la más reciente ES la de
     * hoy, así que alcanza con mirar getLatestRate() (ya cacheado). La cache la
     * invalida RateObserver al crear/actualizar/borrar una tasa, así que el
     * aviso desaparece en la request siguiente a registrar la tasa.
     */
    public function notice(): ?RateNotice
    {
        if (config('rates.notice_enabled') !== true) {
            return null;
        }

        $rate = $this->getLatestRate();

        if ($rate === null) {
            return new RateNotice(
                lastRateDate: null,
                daysStale: null,
                severity: RateSeverity::CRITICAL,
                message: 'No hay ninguna tasa de cambio registrada. Las conversiones y el diferencial cambiario se calcularán con valores por defecto.',
            );
        }

        // Negativo si la tasa tiene fecha futura: no se avisa, está al día.
        // round() porque diffInDays devuelve float en Carbon 3 y truncar un
        // 2.9999999 a 2 mostraría un día menos de los reales.
        $daysStale = (int) round($rate->rate_date->startOfDay()->diffInDays(now()->startOfDay()));

        if ($daysStale <= (int) config('rates.stale_after_days')) {
            return null;
        }

        $criticalAfter = (int) config('rates.critical_after_days');

        return new RateNotice(
            lastRateDate: $rate->rate_date->format('Y-m-d'),
            daysStale: $daysStale,
            severity: $daysStale > $criticalAfter ? RateSeverity::CRITICAL : RateSeverity::WARNING,
            message: $daysStale === 1
                ? 'La tasa de cambio no se actualiza desde ayer. Registrá la de hoy para no operar con valores viejos.'
                : "La tasa de cambio no se actualiza hace {$daysStale} días. Registrá la tasa vigente para que ventas, compras y cobranza no calculen sobre valores viejos.",
        );
    }

    /**
     * Resuelve la tasa de cambio para una moneda y fecha específica.
     * Para USD y BS/VES retorna rate_usd (nunca 1).
     * Para COP retorna rate_cop.
     * Para EUR retorna rate_eur.
     */
    public function resolveRateForCoin(string $date, string $coin): float
    {
        $rate = $this->getBestRateForDate($date);

        if (! $rate) {
            return 1;
        }

        return match (strtoupper($coin)) {
            'USD', 'BS', 'VES' => (float) $rate->rate_usd,
            'COP' => (float) $rate->rate_cop,
            'EUR' => (float) $rate->rate_eur,
            default => (float) $rate->rate_usd,
        };
    }

    /**
     * Obtiene la mejor tasa disponible para una fecha.
     * Busca exacta primero, luego la más reciente anterior, y finalmente la más reciente.
     */
    public function getBestRateForDate(string $date): ?Rate
    {
        $normalized = Carbon::parse($date)->format('Y-m-d');

        $exactRate = Rate::whereDate('rate_date', $normalized)->first();

        if ($exactRate) {
            return $exactRate;
        }

        $pastRate = Rate::whereDate('rate_date', '<', $normalized)
            ->orderBy('rate_date', 'desc')
            ->first();

        if ($pastRate) {
            return $pastRate;
        }

        return Rate::orderBy('rate_date', 'desc')->first();
    }

    /**
     * Obtiene la tasa exacta para una fecha (requerida para cobranzas).
     */
    public function getExactRateForDate(string $date): ?Rate
    {
        return Rate::whereDate('rate_date', Carbon::parse($date)->format('Y-m-d'))->first();
    }

    /**
     * Convierte un monto de una moneda a USD usando el registro Rate dado.
     */
    public function convertToUsd(float $amount, string $coin, Rate $rate): float
    {
        return Rate::convert($amount, $coin, 'USD', $rate);
    }

    /**
     * Calcula el diferencial cambiario en USD entre la tasa del documento y la tasa del pago.
     * Retorna positivo si es ganancia, negativo si es pérdida.
     */
    public function calculateDiferencial(
        float $paymentAmount,
        string $payCoin,
        Rate $payRate,
        Rate $docRate,
    ): float {
        if (strtoupper($payCoin) === 'USD') {
            return 0.0;
        }

        $amountAtDocRate = Rate::convert($paymentAmount, $payCoin, 'USD', $docRate);
        $amountAtPayRate = Rate::convert($paymentAmount, $payCoin, 'USD', $payRate);

        return round($amountAtDocRate - $amountAtPayRate, 4);
    }
}
