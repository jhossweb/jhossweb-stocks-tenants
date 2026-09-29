<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Columnas reales de la tabla `tenants`. Todo atributo que no esté aquí
     * se serializa en la columna `data` (VirtualColumn).
     */
    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'status', 'plan'];
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Primer dominio del tenant (o null).
     */
    public function domain(): ?string
    {
        return $this->domains()->value('domain');
    }
}
