<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Settings\UserAccessNames;
use App\Tenancy\RoutePermissionMap;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function __construct(
        private readonly UserAccessNames $accessNames,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('testing') && ! config('tenancy.enforce_permissions_in_tests', false)) {
            return $next($request);
        }

        if (RoutePermissionMap::isAllowed($request)) {
            return $next($request);
        }

        $permission = RoutePermissionMap::permissionFor($request);

        if ($permission === null) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        $user = $request->user();

        if ($user === null || ! in_array($permission, $this->accessNames->for($user)['permissions'], true)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
