<?php

namespace App\Enum\Sales;

enum StatusSales: string
{
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case PENDING = 'pending';
    case PARTIAL = 'partial';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
