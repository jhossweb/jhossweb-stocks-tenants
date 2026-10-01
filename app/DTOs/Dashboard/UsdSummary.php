<?php

namespace App\DTOs\Dashboard;

use App\Models\Rate;

/**
 * Un total multi-moneda junto a su equivalente en dólares.
 *
 * `usd` es null cuando no hay ninguna tasa cargada: es un estado legítimo
 * (un tenant recién creado), no un error, y el frontend debe poder seguir
 * mostrando el desglose por moneda sin el equivalente.
 *
 * La conversión usa la tasa MÁS RECIENTE, no la de cada documento. Los
 * documentos guardan su propia tasa histórica (`*_valorcam`), así que
 * sumar el equivalente USD documento por documento daría otra cifra: la
 * históricamente exacta pero que cambia de implementación a implementación.
 * Convertir el agregado mantiene los KPI y el detalle (categories, rows)
 * cuadrando entre sí, a cambio de que un mes histórico se recalcule cuando se
 * cargue una tasa nueva.
 */
readonly class UsdSummary
{
    /**
     * @param  array<string, float>  $byCurrency  Totales por moneda, sin transformar.
     */
    public function __construct(
        public array $byCurrency,
        public ?float $usd,
        public ?string $rateDate,
    ) {}

    public function hasRate(): bool
    {
        return $this->usd !== null;
    }

    /**
     * @param  array<string, mixed>  $byCurrency
     */
    public static function from(array $byCurrency, ?Rate $rate): self
    {
        $normalized = [];

        foreach ($byCurrency as $coin => $amount) {
            $normalized[(string) $coin] = round((float) $amount, 4);
        }

        if ($rate === null) {
            return new self(byCurrency: $normalized, usd: null, rateDate: null);
        }

        $usd = 0.0;

        foreach ($normalized as $coin => $amount) {
            $usd += Rate::convert($amount, $coin, 'USD', $rate);
        }

        return new self(
            byCurrency: $normalized,
            usd: round($usd, 4),
            rateDate: $rate->rate_date?->format('Y-m-d'),
        );
    }

    /**
     * @return array{usd: float|null, by_currency: array<string, float>, rate_date: string|null, has_rate: bool}
     */
    public function toArray(): array
    {
        return [
            'usd' => $this->usd,
            'by_currency' => $this->byCurrency,
            'rate_date' => $this->rateDate,
            'has_rate' => $this->hasRate(),
        ];
    }
}
