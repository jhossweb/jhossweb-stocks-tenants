<?php

namespace App\DTOs\Finance\Rate;

use App\Enum\Finanzas\RateSeverity;

/**
 * Aviso de tasa de cambio faltante o vencida, compartido a todas las páginas
 * del tenant para que el usuario registre la tasa antes de operar con valores
 * convertidos desactualizados.
 */
readonly class RateNotice
{
    public function __construct(
        public ?string $lastRateDate,
        public ?int $daysStale,
        public RateSeverity $severity,
        public string $message,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'last_rate_date' => $this->lastRateDate,
            'days_stale' => $this->daysStale,
            'severity' => $this->severity->value,
            'message' => $this->message,
        ];
    }
}
