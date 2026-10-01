<?php

use App\Enum\Expense\ExpenseStatus;
use App\Enum\Expense\ExpenseType;
use App\Enum\TypeCoin;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedAnalyticsCategory(string $name, ExpenseType $type): ExpenseCategory
{
    return ExpenseCategory::factory()->create(['exc_name' => $name, 'exc_type' => $type]);
}

function seedAnalyticsExpense(ExpenseCategory $category, TypeCoin $coin, float $amount): Expense
{
    return Expense::factory()->create([
        'exp_category_id' => $category->id,
        'exp_coin' => $coin,
        'exp_amount' => $amount,
        'exp_paid_amount' => $amount,
        'exp_date' => now()->format('Y-m-d'),
        'exp_status' => ExpenseStatus::PAID,
    ]);
}

/**
 * Resuelve la prop `details`, que AnalyticsController envía diferida.
 *
 * @return array<string, mixed>
 */
function analyticsDetails(string $type): array
{
    $route = route('analytics.show', ['type' => $type]);

    $version = (string) test()->get($route)->viewData('page')['version'];

    return test()->get($route, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'Analytics/Expenses',
        'X-Inertia-Partial-Data' => 'details',
    ])->assertOk()->json('props.details');
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    seedAnalyticsExpense(
        seedAnalyticsCategory('Alquiler', ExpenseType::FIJO),
        TypeCoin::COP,
        4000,
    );

    seedAnalyticsExpense(
        seedAnalyticsCategory('Servicios', ExpenseType::VARIABLE),
        TypeCoin::USD,
        100,
    );
});

test('expenses analytics returns one flat row per category and currency', function () {
    $rows = analyticsDetails('expenses')['byCategory'];

    expect($rows)->toHaveCount(2);

    foreach ($rows as $row) {
        expect($row)->toHaveKeys(['exc_name', 'exc_type', 'exp_coin', 'total']);
    }

    $byName = collect($rows)->keyBy('exc_name');

    expect($byName['Alquiler']['exc_type'])->toBe('fijo')
        ->and($byName['Alquiler']['exp_coin'])->toBe('COP')
        ->and($byName['Alquiler']['total'])->toEqual(4000.0)
        ->and($byName['Servicios']['exc_type'])->toBe('variable')
        ->and($byName['Servicios']['exp_coin'])->toBe('USD')
        ->and($byName['Servicios']['total'])->toEqual(100.0);
});

test('expenses analytics by fixed type only returns fixed categories', function () {
    $rows = analyticsDetails('expenses-fijo')['byCategory'];

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['exc_name'])->toBe('Alquiler')
        ->and($rows[0]['exc_type'])->toBe('fijo')
        ->and($rows[0]['exp_coin'])->toBe('COP')
        ->and($rows[0]['total'])->toEqual(4000.0);
});

test('expenses analytics by variable type only returns variable categories', function () {
    $rows = analyticsDetails('expenses-variable')['byCategory'];

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['exc_name'])->toBe('Servicios')
        ->and($rows[0]['exc_type'])->toBe('variable')
        ->and($rows[0]['exp_coin'])->toBe('USD')
        ->and($rows[0]['total'])->toEqual(100.0);
});
