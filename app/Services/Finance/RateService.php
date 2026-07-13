<?php

namespace App\Services\Finance;

use App\Models\Rate;
use Carbon\Carbon;

class RateService
{
    /**
     * Obtiene la tasa más reciente.
     */
    public function getLatestRate(): ?Rate
    {
        return Rate::orderBy('rate_date', 'desc')->first();
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

        $exactRate = Rate::where('rate_date', $normalized)->first();

        if ($exactRate) {
            return $exactRate;
        }

        $pastRate = Rate::where('rate_date', '<', $normalized)
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
        return Rate::where('rate_date', Carbon::parse($date)->format('Y-m-d'))->first();
    }
}
