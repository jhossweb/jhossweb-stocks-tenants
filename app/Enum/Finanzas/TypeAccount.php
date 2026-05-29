<?php

namespace App\Enum\Finanzas;

enum TypeAccount: string
{
    case AHORRO = 'AHORRO';
    case CORRIENTE = 'CORRIENTE';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
