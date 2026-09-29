<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class InitializeTenancy
{
    /**
     * Inicializa la tenancy por subdominio para las rutas de tenant.
     *
     * En el entorno de testing la tenancy se omite por completo para que
     * los tests sigan corriendo contra el schema único sin tenancy activo.
     * Si no se puede identificar un tenant (host central o subdominio
     * desconocido), las rutas de tenant responden 404.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        try {
            return app(InitializeTenancyByDomain::class)->handle($request, function (Request $request) use ($next) {
                // Un tenant suspendido queda inaccesible desde su subdominio
                // (el estado `status` se gestiona desde el panel central).
                if (tenancy()->initialized && ! tenant()->isActive()) {
                    abort(403, 'El tenant está suspendido.');
                }

                return $next($request);
            });
        } catch (TenantCouldNotBeIdentifiedException) {
            abort(404);
        }
    }
}
