<?php

declare(strict_types=1);

namespace App\Tenancy;

use ReflectionProperty;
use Spatie\Permission\PermissionRegistrar as BasePermissionRegistrar;

/**
 * Permite limpiar el estado en memoria del registrar sin borrar la caché
 * persistente. Necesario al cambiar de tenant en procesos long-running
 * (queue workers / Octane): la caché por tenant se conserva entre requests,
 * pero no se reutiliza la colección cargada para otro tenant.
 */
class PermissionRegistrar extends BasePermissionRegistrar
{
    public function resetInMemoryState(): void
    {
        $this->permissions = null;

        foreach (['cachedRoles', 'alias', 'except', 'wildcardPermissionsIndex'] as $property) {
            $reflection = new ReflectionProperty(BasePermissionRegistrar::class, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($this, []);
        }

        $reflection = new ReflectionProperty(BasePermissionRegistrar::class, 'isLoadingPermissions');
        $reflection->setAccessible(true);
        $reflection->setValue($this, false);
    }
}
