<?php

namespace App\Enum\Finanzas;

enum TypePayment: string
{
    case PAYMENT = 'PAYMENT';
    case RETURN_CREDIT = 'RETURN_CREDIT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
