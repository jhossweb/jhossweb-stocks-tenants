<?php

namespace App\Enum\Employees;

enum EmployeeStatus: string
{
    case ACTIVE = 'activo';
    case INACTIVE = 'inactivo';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
