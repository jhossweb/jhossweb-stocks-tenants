<?php

namespace App\Enum\Expense;

enum ExpenseStatus: string
{
    case PENDING = 'pendiente';
    case PARTIAL = 'parcial';
    case PAID = 'pagado';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
