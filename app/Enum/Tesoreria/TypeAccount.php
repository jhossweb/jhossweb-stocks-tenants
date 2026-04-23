<?php

namespace App\Enum\Tesoreria;

enum TypeAccount: string
{
    case AHORRO = 'AHORRO';
    case CORRIENTE = 'CORRIENTE';

    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

}