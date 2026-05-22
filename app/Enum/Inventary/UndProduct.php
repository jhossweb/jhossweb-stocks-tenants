<?php

namespace App\Enum\Inventary;

enum UndProduct: string
{
    case UND = 'und';
    case MTS = 'mts';
    case KG = 'kg';
    case M2 = 'm2';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
