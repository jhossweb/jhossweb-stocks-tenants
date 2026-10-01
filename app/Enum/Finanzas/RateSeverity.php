<?php

namespace App\Enum\Finanzas;

enum RateSeverity: string
{
    case WARNING = 'warning';

    case CRITICAL = 'critical';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
