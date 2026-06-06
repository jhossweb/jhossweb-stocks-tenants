<?php

namespace App\Enum\Payroll;

enum PayrollPeriodStatus: string
{
    case PENDING = 'pendiente';
    case PAID = 'pagado';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
