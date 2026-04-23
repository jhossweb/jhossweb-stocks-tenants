<?php

namespace App\Enum\Tesoreria;

enum TypePayment: string
{
    case PAYMENT = 'PAYMENT';
    case RETURN_CREDIT = 'RETURN_CREDIT';

    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}