<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum Currency: string
{
    case USD = 'USD';
    case COP = 'COP';
    case BS = 'BS';
    case VES = 'VES';
    case EUR = 'EUR';
}
