<?php

namespace App\Enum\Payroll;

enum DamageStatus: string
{
    case ACTIVE = 'activo';
    case PAID = 'pagado';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
