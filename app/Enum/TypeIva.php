<?php

namespace App\Enum;

enum TypeIva: string
{
    case GN = "16";
    case RE = "8";
    case EX = "0";


    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}