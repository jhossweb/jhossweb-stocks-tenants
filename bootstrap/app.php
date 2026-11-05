<?php

use App\Exceptions\UsageLimitExceededException;
use App\Http\Middleware\EnsureCentralAdmin;
use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\EnsureTenantFeature;
use App\Http\Middleware\EnsureTenantModule;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InitializeTenancy;
use App\Http\Middleware\OnlyCentralDomains;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'module' => EnsureTenantModule::class,
            'feature' => EnsureTenantFeature::class,
            'active.user' => EnsureUserIsActive::class,
            'permission.route' => EnsureRoutePermission::class,
            'tenant' => InitializeTenancy::class,
            'central' => OnlyCentralDomains::class,
            'admin' => EnsureCentralAdmin::class,
            'ensure.central.admin' => EnsureCentralAdmin::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin/*') || $request->is('admin')) {
                return route('admin.login');
            }

            return route('login');
        });
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Middleware de tenancy por subdominio. Se aplica únicamente al grupo
        // de rutas 'tenant' (ERP + settings), nunca a las rutas centrales
        // (portal SaaS en localhost). La clase omite la tenancy en testing.
        // 'central' limita las rutas del portal central a los dominios
        // centrales (localhost / 127.0.0.1).
        // La tenancy debe inicializarse antes que la autenticación y el
        // binding de modelos para que ambos se resuelvan contra el schema
        // del tenant (y no contra el schema central). Anclamos nuestro
        // middleware justo antes de la autenticación en la prioridad.
        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            InitializeTenancy::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UsageLimitExceededException $exception, Request $request) {
            if ($request->header('X-Inertia')) {
                return back()->with('error', $exception->getMessage());
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        });

        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception) {
            $request = request();
            $code = $response->getStatusCode();
            $isInertia = $request->header('X-Inertia');

            if (! $isInertia) {
                return $response;
            }

            $friendlyMap = [
                NotFoundHttpException::class => 404,
                ModelNotFoundException::class => 404,
                AuthorizationException::class => 403,
            ];

            $friendlyCode = null;
            foreach ($friendlyMap as $exceptionClass => $httpCode) {
                if ($exception instanceof $exceptionClass) {
                    $friendlyCode = $httpCode;
                    break;
                }
            }

            if ($exception instanceof QueryException) {
                $friendlyCode = 500;
            }

            if ($friendlyCode === null && $code < 500) {
                return $response;
            }

            $renderCode = $friendlyCode ?? $code;

            return Inertia::render('Error', ['status' => $renderCode])
                ->toResponse($request)
                ->setStatusCode($renderCode);
        });
    })->create();
