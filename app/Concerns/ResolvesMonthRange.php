<?php

declare(strict_types=1);

namespace App\Concerns;

use Carbon\CarbonImmutable;

/**
 * Resuelve un período 'YYYY-MM' al rango de fechas que lo cubre.
 *
 * Los KPI de dashboard filtran por mes con comparaciones sobre la columna
 * directa, nunca con `TO_CHAR(columna, 'YYYY-MM')`: TO_CHAR envuelve la columna
 * en una función y con eso el índice sobre la fecha queda inservible.
 *
 * Para los WHERE se usa `monthBounds()`, un intervalo semiabierto
 * `[primer día, primer día del mes siguiente)`. No es cosmético: en SQLite las
 * fechas se guardan como texto con hora ('2026-09-30 00:00:00'), así que un
 * `BETWEEN '2026-09-01' AND '2026-09-30'` excluye el último día del mes
 * ('2026-09-30 00:00:00' > '2026-09-30'). El intervalo semiabierto es correcto
 * en ambos motores, indexable y no deja ambigüedad inclusiva/exclusiva.
 */
trait ResolvesMonthRange
{
    /**
     * Rango inclusivo del mes indicado, con default al mes en curso.
     *
     * Pensado para presentation y cálculos, no para consultas SQL: para
     * filtrar usar monthBounds().
     *
     * @return array{0: string, 1: string} [desde, hasta] en formato Y-m-d
     */
    protected function monthRange(?string $month = null): array
    {
        $start = $this->monthStart($month);

        return [$start->toDateString(), $start->endOfMonth()->toDateString()];
    }

    /**
     * Intervalo semiabierto del mes para cláusulas WHERE sobre la fecha cruda.
     *
     * @return array{0: string, 1: string} [desde, primer día del mes siguiente]
     */
    protected function monthBounds(?string $month = null): array
    {
        $start = $this->monthStart($month);

        return [$start->toDateString(), $start->addMonthNoOverflow()->toDateString()];
    }

    /**
     * Rango inclusivo del mes anterior al indicado, para calcular tendencias.
     *
     * @return array{0: string, 1: string} [desde, hasta] en formato Y-m-d
     */
    protected function previousMonthRange(string $month): array
    {
        return $this->monthRange($this->previousMonth($month));
    }

    /**
     * Mes inmediatamente anterior al indicado, en formato Y-m.
     */
    protected function previousMonth(string $month): string
    {
        return $this->monthStart($month)->subMonth()->format('Y-m');
    }

    private function monthStart(?string $month = null): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m', $month ?? now()->format('Y-m'))->startOfMonth();
    }
}
