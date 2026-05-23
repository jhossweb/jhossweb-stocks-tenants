<?php

namespace App\Enum\Inventary;

enum ProType: string
{
    case SIMPLE = 'simple';
    case COMPUESTO = 'compuesto';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
