<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum PaymentMethod: string
{
    case TRANSFER = 'transfer';
    case MOBILE_PAYMENT = 'mobile_payment';
    case CASH = 'cash';
    case CARD = 'card';
    case OTHER = 'other';
}
