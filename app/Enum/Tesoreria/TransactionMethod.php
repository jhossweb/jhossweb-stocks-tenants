<?php

namespace App\Enum\Tesoreria;

enum TransactionMethod: string
{
    case CASH = 'EFECTIVO';
    case TRANSFER = 'TRANSFERENCIA';
    case CHECK = 'CHEQUE';
    case OTHER = 'OTRO';

    static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
