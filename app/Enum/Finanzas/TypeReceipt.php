<?php

namespace App\Enum\Finanzas;

enum TypeReceipt: string
{
    case RECEIPT = 'RECEIPT';
    case RETURN_CREDIT = 'RETURN_CREDIT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
