<?php

use App\DTOs\Dashboard\UsdSummary;
use App\Enum\Expense\ExpenseStatus;
use App\Enum\Expense\ExpenseType;
use App\Enum\TypeCoin;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Rate;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createExpenseCategory(string $name, ExpenseType $type): ExpenseCategory
{
    return ExpenseCategory::factory()->create(['exc_name' => $name, 'exc_type' => $type]);
}

function createExpense(ExpenseCategory $category, TypeCoin $coin, float $amount, string $date): Expense
{
    return Expense::factory()->create([
        'exp_category_id' => $category->id,
        'exp_coin' => $coin,
        'exp_amount' => $amount,
        'exp_paid_amount' => $amount,
        'exp_date' => $date,
        'exp_status' => ExpenseStatus::PAID,
    ]);
}

test('usd summary sums every currency into a single usd total', function () {
    $rate = Rate::factory()->create([
        'rate_date' => '2026-01-10',
        'rate_usd' => 1,
        'rate_cop' => 4000,
        'rate_eur' => 0.9,
        'rate_cop_bs' => 0,
    ]);

    $summary = UsdSummary::from(['USD' => 100, 'COP' => 4000], $rate);

    expect($summary->usd)->toBe(101.0)
        ->and($summary->byCurrency)->toBe(['USD' => 100.0, 'COP' => 4000.0])
        ->and($summary->hasRate())->toBeTrue()
        ->and($summary->rateDate)->toBe('2026-01-10');
});

test('usd summary is null when no rate is loaded', function () {
    $summary = UsdSummary::from(['COP' => 4000], null);

    expect($summary->usd)->toBeNull()
        ->and($summary->hasRate())->toBeFalse()
        ->and($summary->rateDate)->toBeNull()
        // El desglose nativo sobrevive: el frontend puede seguir en multi-moneda.
        ->and($summary->byCurrency)->toBe(['COP' => 4000.0]);
});

test('expense breakdown groups by category and shares add up to 100', function () {
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 4000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $rent = createExpenseCategory('Alquiler', ExpenseType::FIJO);
    $utilities = createExpenseCategory('Servicios', ExpenseType::VARIABLE);
    $salary = createExpenseCategory('Nómina', ExpenseType::FIJO);

    $today = now()->format('Y-m-d');

    // 100 USD + 800.000 COP (= 200 USD) => 300 USD en alquiler
    createExpense($rent, TypeCoin::USD, 100, $today);
    createExpense($rent, TypeCoin::COP, 800000, $today);
    // 50 USD en servicios
    createExpense($utilities, TypeCoin::USD, 50, $today);
    // Gasto del mes pasado: no debe aparecer
    createExpense($salary, TypeCoin::USD, 999, now()->subMonth()->format('Y-m-d'));

    $result = app(DashboardService::class)->getExpenseBreakdown(now()->format('Y-m'));

    expect($result['usd'])->toBe(350.0)
        ->and($result['total_by_currency'])->toMatchArray(['USD' => 150.0, 'COP' => 800000.0])
        ->and($result['categories'])->toHaveCount(2)
        // Ordenado de mayor a menor
        ->and($result['categories'][0]['exc_name'])->toBe('Alquiler')
        ->and($result['categories'][0]['usd'])->toBe(300.0)
        ->and($result['categories'][0]['share_pct'])->toBe(85.7)
        ->and($result['categories'][1]['exc_name'])->toBe('Servicios')
        ->and($result['categories'][1]['share_pct'])->toBe(14.3);

    $sum = array_sum(array_column($result['categories'], 'share_pct'));

    expect(round($sum, 1))->toBe(100.0);
});

test('expense breakdown share is zero without a rate instead of a fake percentage', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);
    createExpense($category, TypeCoin::COP, 100000, now()->format('Y-m-d'));

    $result = app(DashboardService::class)->getExpenseBreakdown();

    expect($result['usd'])->toBeNull()
        ->and($result['has_rate'])->toBeFalse()
        // Sin denominador comparable no se reparte nada.
        ->and($result['categories'][0]['share_pct'])->toBe(0.0)
        // Pero el monto nativo sigue disponible.
        ->and($result['categories'][0]['amounts'])->toBe(['COP' => 100000.0]);
});

test('expense breakdown respects the last day of the month', function () {
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 4000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);

    // El último día del mes: el patrón BETWEEN colaba 'YYYY-MM-DD 00:00:00'
    // y SQLite guarda 'YYYY-MM-DD HH:MM:SS', así que esto antes se perdía.
    createExpense($category, TypeCoin::USD, 42, now()->endOfMonth()->format('Y-m-d'));

    $result = app(DashboardService::class)->getExpenseBreakdown();

    expect($result['usd'])->toBe(42.0)
        ->and($result['categories'])->toHaveCount(1);
});

test('expense breakdown excludes the first day of the next month', function () {
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 4000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);

    createExpense($category, TypeCoin::USD, 10, now()->format('Y-m-d'));
    // El día 1 del mes siguiente pertenece al mes siguiente, no a éste.
    createExpense($category, TypeCoin::USD, 500, now()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'));

    $result = app(DashboardService::class)->getExpenseBreakdown();

    expect($result['usd'])->toBe(10.0);
});

test('dashboard aggregates expose usd equivalents', function () {
    // 4.000 COP / 3.000 = 1.333 USD
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 3000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);
    createExpense($category, TypeCoin::COP, 4000, now()->format('Y-m-d'));

    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('categoryAggregates.gastos.usd', 1.3333)
            ->where('categoryAggregates.gastos.has_rate', true)
            ->where('categoryAggregates.gastos.rate_date', now()->format('Y-m-d')));
});

test('trend compares usd equivalents instead of summing mixed currencies', function () {
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 4000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);

    // Mes actual: 4000 COP = 1 USD
    createExpense($category, TypeCoin::COP, 4000, now()->format('Y-m-d'));
    // Mes anterior: 8000 COP = 2 USD  =>  -50 %
    createExpense($category, TypeCoin::COP, 8000, now()->subMonth()->format('Y-m-d'));

    $kpis = app(DashboardService::class)->getKpis(now()->format('Y-m'));

    $trend = $kpis['expensesFijo']['trend'];

    expect($trend['direction'])->toBe('down')
        ->and($trend['percentage'])->toBe(50.0)
        ->and($trend['reliable'])->toBeTrue()
        // El payload multi-moneda se conserva intacto
        ->and($kpis['expensesFijo']['total'])->toEqual(['COP' => 4000])
        ->and($kpis['expensesFijo']['usd'])->toBe(1.0);
});

test('trend is flagged unreliable when no rate is loaded', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);
    createExpense($category, TypeCoin::COP, 4000, now()->format('Y-m-d'));
    createExpense($category, TypeCoin::COP, 8000, now()->subMonth()->format('Y-m-d'));

    $kpis = app(DashboardService::class)->getKpis(now()->format('Y-m'));

    expect($kpis['expensesFijo']['usd'])->toBeNull()
        ->and($kpis['expensesFijo']['trend']['reliable'])->toBeFalse();
});

test('gastos page renders with the deferred expense breakdown', function () {
    $rate = Rate::factory()->create(['rate_date' => now()->format('Y-m-d'), 'rate_usd' => 1, 'rate_cop' => 4000, 'rate_cop_bs' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = createExpenseCategory('Alquiler', ExpenseType::FIJO);
    createExpense($category, TypeCoin::USD, 100, now()->format('Y-m-d'));

    // La carga inicial (HTML) da la versión de assets que exige Inertia.
    $version = (string) $this->get(route('dashboard.gastos'))->viewData('page')['version'];

    // La primera carga JSON trae los KPI pero no la tabla: eso es lo diferido.
    $first = $this->get(route('dashboard.gastos'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ])->assertOk();

    expect($first->json('component'))->toBe('Dashboard/Gastos')
        ->and($first->json('props'))->toHaveKey('kpis.expensesFijo')
        ->and($first->json('props'))->not->toHaveKey('expenseBreakdown');

    // Se simula la request parcial que hace el navegador al resolverla.
    $partial = $this->get(route('dashboard.gastos'), [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'Dashboard/Gastos',
        'X-Inertia-Partial-Data' => 'expenseBreakdown',
    ]);

    $partial->assertOk()->assertHeader('Vary', 'X-Inertia');

    $props = $partial->json('props');

    expect($props)->toHaveKey('expenseBreakdown')
        ->and($props['expenseBreakdown']['usd'])->toEqual(100.0)
        ->and($props['expenseBreakdown']['has_rate'])->toBeTrue()
        ->and($props['expenseBreakdown']['total_by_currency']['USD'])->toEqual(100.0)
        ->and($props['expenseBreakdown']['categories'])->toHaveCount(1)
        ->and($props['expenseBreakdown']['categories'][0]['exc_name'])->toBe('Alquiler')
        ->and($props['expenseBreakdown']['categories'][0]['usd'])->toEqual(100.0)
        ->and($props['expenseBreakdown']['categories'][0]['share_pct'])->toEqual(100.0)
        // La prop diferida no arrastra los KPI: esa parte ya llegó antes.
        ->and($props)->not->toHaveKey('kpis');
});
