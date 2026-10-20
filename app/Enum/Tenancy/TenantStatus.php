<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum TenantStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
