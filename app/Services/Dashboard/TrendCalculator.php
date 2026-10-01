<?php

namespace App\Services\Dashboard;

use App\Models\Rate;

/**
 * Variación porcentual de un KPI multi-moneda contra su período anterior.
 *
 * Compara los equivalentes en USD de ambos períodos, nunca la suma cruda de
 * las monedas: sumar USD + COP + BS no es una magnitud, y el porcentaje que
 * salía de esa suma no significaba nada.
 *
 * Sin tasa cargada no hay base comparable. En ese caso se cae a la suma nativa
 * y el resultado se marca como no confiable para que la UI pueda advertirlo, en
 * vez de publicar un porcentaje inventado.
 */
final class TrendCalculator
{
    /**
     * @param  array<string, float>  $current
     * @param  array<string, float>  $previous
     * @return array{direction: string, percentage: float, reliable: bool}
     */
    public static function compute(array $current, array $previous, ?Rate $rate): array
    {
        if ($rate === null) {
            return self::fromTotals(self::rawTotal($current), self::rawTotal($previous), reliable: false);
        }

        return self::fromTotals(
            self::toUsd($current, $rate),
            self::toUsd($previous, $rate),
            reliable: true,
        );
    }

    /**
     * @return array{direction: string, percentage: float, reliable: bool}
     */
    public static function fromTotals(?float $current, ?float $previous, bool $reliable = true): array
    {
        $currentTotal = $current ?? 0.0;
        $previousTotal = $previous ?? 0.0;

        if ($previousTotal <= 0) {
            return ['direction' => 'flat', 'percentage' => 0.0, 'reliable' => $reliable];
        }

        $percentage = round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1);

        return [
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'flat'),
            'percentage' => abs($percentage),
            'reliable' => $reliable,
        ];
    }

    /**
     * @param  array<string, float>  $byCurrency
     */
    private static function toUsd(array $byCurrency, Rate $rate): float
    {
        $total = 0.0;

        foreach ($byCurrency as $coin => $amount) {
            $total += Rate::convert((float) $amount, (string) $coin, 'USD', $rate);
        }

        return $total;
    }

    /**
     * @param  array<string, float>  $byCurrency
     */
    private static function rawTotal(array $byCurrency): float
    {
        return (float) array_sum(array_map('floatval', $byCurrency));
    }
}
