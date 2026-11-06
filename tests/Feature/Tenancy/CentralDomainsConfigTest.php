<?php

use App\Http\Middleware\OnlyCentralDomains;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

it('incluye los dominios centrales declarados por entorno y por railway', function () {
    $_ENV['TENANCY_CENTRAL_DOMAINS'] = 'central.test, panel.test';
    $_SERVER['TENANCY_CENTRAL_DOMAINS'] = 'central.test, panel.test';
    $_ENV['RAILWAY_PUBLIC_DOMAIN'] = 'app.up.railway.app';
    $_SERVER['RAILWAY_PUBLIC_DOMAIN'] = 'app.up.railway.app';

    try {
        $config = require config_path('tenancy.php');
    } finally {
        unset(
            $_ENV['TENANCY_CENTRAL_DOMAINS'],
            $_SERVER['TENANCY_CENTRAL_DOMAINS'],
            $_ENV['RAILWAY_PUBLIC_DOMAIN'],
            $_SERVER['RAILWAY_PUBLIC_DOMAIN'],
        );
    }

    expect($config['central_domains'])
        ->toContain('127.0.0.1')
        ->toContain('localhost')
        ->toContain('central.test')
        ->toContain('panel.test')
        ->toContain('app.up.railway.app');
});

it('permite el portal central desde un dominio configurado', function () {
    config(['tenancy.central_domains' => ['app.up.railway.app']]);

    $request = Request::create('https://app.up.railway.app/admin');

    $response = app(OnlyCentralDomains::class)->handle(
        $request,
        fn () => response('ok'),
    );

    expect($response->getContent())->toBe('ok');
});

it('rechaza el portal central desde un subdominio de tenant', function () {
    config(['tenancy.central_domains' => ['app.up.railway.app']]);

    $request = Request::create('https://empresa.up.railway.app/admin');

    expect(fn () => app(OnlyCentralDomains::class)->handle(
        $request,
        fn () => response('ok'),
    ))->toThrow(NotFoundHttpException::class);
});
