<?php

namespace App\Enum\Finanzas;

enum TransactionMethod: string
{
    case CASH = 'EFECTIVO';
    case TRANSFER = 'TRANSFERENCIA';
    case CHECK = 'CHEQUE';
    case OTHER = 'OTRO';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
