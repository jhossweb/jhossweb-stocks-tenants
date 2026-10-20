<?php

declare(strict_types=1);

namespace App\Enum\Tenancy;

enum TenantEventType: string
{
    case TENANT_CREATED = 'tenant_created';
    case TENANT_UPDATED = 'tenant_updated';
    case PLAN_ASSIGNED = 'plan_assigned';
    case PLAN_CHANGED = 'plan_changed';
    case TENANT_ACTIVATED = 'tenant_activated';
    case TENANT_SUSPENDED = 'tenant_suspended';
    case TENANT_CANCELED = 'tenant_canceled';
    case PAYMENT_REGISTERED = 'payment_registered';
    case PAYMENT_VOIDED = 'payment_voided';
    case PASSWORD_RESET = 'password_reset';
    case STATUS_CHANGED = 'status_changed';
}
