<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCentralAdmin
{
    /**
     * Restringe las rutas de administración del portal central a los
     * usuarios con `is_admin = true`. Se espera que `auth` haya corrido antes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_admin !== true) {
            abort(403);
        }

        return $next($request);
    }
}
