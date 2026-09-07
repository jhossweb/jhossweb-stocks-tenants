<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
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
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
                $friendlyCode = 422;
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
