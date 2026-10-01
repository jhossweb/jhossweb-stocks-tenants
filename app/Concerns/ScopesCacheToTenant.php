<?php

declare(strict_types=1);

namespace App\Concerns;

/**
 * Scope de claves de caché por tenant.
 *
 * `config/tenancy.php` desactiva `CacheTenancyBootstrapper` porque
 * `CACHE_STORE=file` no soporta tags. Consecuencia: sin este scope, una clave
 * escrita por un tenant la leen todos los demás (fuga real de datos entre
 * empresas). Cualquier caché de datos de negocio DEBE pasar por acá.
 *
 * El sentinel `central` cubre el portal central, donde no hay tenant.
 */
trait ScopesCacheToTenant
{
    /**
     * Es estático para poder invocarse tanto desde métodos de instancia
     * (`self::tenantCacheKey(...)`) como desde APIs públicas estáticas que otros
     * servicios necesitan leer (p. ej. un Observer que invalida la caché).
     */
    public static function tenantCacheKey(string $key): string
    {
        return $key.'.'.(tenancy()->initialized ? tenant('id') : 'central');
    }
}
