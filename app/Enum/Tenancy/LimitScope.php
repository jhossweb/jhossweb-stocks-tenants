<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum LimitScope: string
{
    case Monthly = 'monthly';
    case Total = 'total';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Mensual',
            self::Total => 'Total',
        };
    }
}
