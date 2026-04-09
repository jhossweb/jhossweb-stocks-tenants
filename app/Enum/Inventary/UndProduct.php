<?php

namespace App\Enum\Inventary;

enum UndProduct: string
{
    case UND = 'und';
    case MTS = 'mts';
    case KG = 'kg';

    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}