<?php

namespace App\Enum;

enum TypeCoin: string
{

    case USD = 'USD';
    case COP = 'COP';

    case BS = 'BS';
    case EUR = 'EUR';

    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

}