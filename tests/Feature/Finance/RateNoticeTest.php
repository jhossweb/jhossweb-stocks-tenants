<?php

use App\Enum\Finanzas\RateSeverity;
use App\Models\Rate;
use App\Models\User;
use App\Services\Finance\RateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Finder\Finder;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->service = new RateService;
});

test('no avisa cuando hay tasa cargada para hoy', function () {
    Rate::factory()->create(['rate_date' => today()]);

    expect($this->service->notice())->toBeNull();
});

test('avisa en crítico cuando el tenant no tiene ninguna tasa', function () {
    $notice = $this->service->notice();

    expect($notice)->not->toBeNull()
        ->and($notice->lastRateDate)->toBeNull()
        ->and($notice->daysStale)->toBeNull()
        ->and($notice->severity)->toBe(RateSeverity::CRITICAL)
        ->and($notice->message)->toContain('No hay ninguna tasa');
});

test('cuantifica los días de atraso de la última tasa', function () {
    Rate::factory()->create(['rate_date' => today()->subDays(4)]);

    $notice = $this->service->notice();

    expect($notice->daysStale)->toBe(4)
        ->and($notice->lastRateDate)->toBe(today()->subDays(4)->format('Y-m-d'))
        ->and($notice->severity)->toBe(RateSeverity::WARNING)
        ->and($notice->message)->toContain('4 días');
});

test('usa el singular cuando el atraso es de un solo día', function () {
    Rate::factory()->create(['rate_date' => today()->subDay()]);

    expect($this->service->notice()->message)->toContain('desde ayer');
});

test('no avisa si la tasa tiene fecha futura', function () {
    Rate::factory()->create(['rate_date' => today()->addDay()]);

    expect($this->service->notice())->toBeNull();
});

test('respeta el umbral de tolerancia configurado', function () {
    Rate::factory()->create(['rate_date' => today()->subDays(4)]);

    expect($this->service->notice())->not->toBeNull();

    config(['rates.stale_after_days' => 7]);
    Cache::flush();

    expect($this->service->notice())->toBeNull();
});

test('escala a crítico pasado critical_after_days', function () {
    Rate::factory()->create(['rate_date' => today()->subDays(40)]);

    expect($this->service->notice()->severity)->toBe(RateSeverity::CRITICAL);

    config(['rates.critical_after_days' => 60]);
    Cache::flush();

    expect($this->service->notice()->severity)->toBe(RateSeverity::WARNING);
});

test('se puede desactivar el aviso completo', function () {
    config(['rates.notice_enabled' => false]);

    expect($this->service->notice())->toBeNull();
});

test('el aviso serializa los campos que espera el frontend', function () {
    Rate::factory()->create(['rate_date' => today()->subDays(2)]);

    $array = $this->service->notice()->toArray();

    expect($array)->toHaveKeys(['last_rate_date', 'days_stale', 'severity', 'message'])
        ->and($array['severity'])->toBe('warning')
        ->and($array['days_stale'])->toBe(2);
});

test('el observer de tasas invalida la misma clave que usa el service', function () {
    Rate::factory()->create(['rate_date' => today()]);

    $key = RateService::tenantCacheKey('rates:v2');

    // notice() puebla la caché recién creada.
    $this->service->notice();
    expect(Cache::has($key))->toBeTrue();

    // RateObserver corre en created: el aviso debe desaparecer de inmediato.
    Rate::factory()->create(['rate_date' => today()->addDay()]);

    expect(Cache::has($key))->toBeFalse();
});

test('ninguna caché nueva se registra con una clave literal sin scope por tenant', function () {
    // config/tenancy.php desactiva CacheTenancyBootstrapper (CACHE_STORE=file no
    // soporta tags), así que una clave literal se comparte entre tenants.
    //
    // Ratchet: la lista de abajo es la DEUDA CONOCIDA del subsistema Dashboard,
    // que tiene ~18 claves (muchas interpoladas por mes) y necesita su propio
    // refactor. Este test falla si aparece una clave nueva fuera de esa lista.
    $deudaConocida = [
        'app/Services/Dashboard/DashboardCacheInvalidator.php => dashboard:receivables:balance',
        'app/Services/Dashboard/DashboardCacheInvalidator.php => dashboard:payables:balance',
        'app/Observers/ExpenseObserver.php => dashboard:expenses:pending',
    ];

    $offenders = [];

    foreach (['app/Services', 'app/Repositories', 'app/Observers'] as $dir) {
        foreach (Finder::create()->files()->in(base_path($dir)) as $file) {
            preg_match_all(
                "/Cache::(?:remember|put|get|forget)\('([^']+)'/",
                $file->getContents(),
                $matches,
            );

            foreach ($matches[1] as $key) {
                $offenders[] = $dir.'/'.$file->getRelativePathname().' => '.$key;
            }
        }
    }

    expect(array_values(array_diff($offenders, $deudaConocida)))->toBe([]);
});

test('la prop compartida rateNotice es null en el portal central', function () {
    $user = User::factory()->create();

    // Sin tenant inicializado la tabla rates ni existe en el schema public:
    // el guard $isTenant del middleware es lo que evita la query.
    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('rateNotice', null));
});
