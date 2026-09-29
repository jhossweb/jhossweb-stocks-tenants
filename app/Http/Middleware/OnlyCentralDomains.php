<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OnlyCentralDomains
{
    /**
     * Permite el acceso a las rutas del portal central únicamente desde
     * los dominios centrales configurados (localhost / 127.0.0.1). Desde
     * un subdominio de tenant estas rutas responden 404.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! in_array($request->getHost(), config('tenancy.central_domains'), true)) {
            abort(404);
        }

        return $next($request);
    }
}
