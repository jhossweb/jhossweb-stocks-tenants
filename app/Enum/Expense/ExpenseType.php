<?php

namespace App\Enum\Expense;

enum ExpenseType: string
{
    case FIJO = 'fijo';
    case VARIABLE = 'variable';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
