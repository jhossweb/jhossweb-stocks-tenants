<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\CostHistory;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function seedPricedProduct(string $code, string $name, ?float $cost, ?array $prices): Product
{
    $user = User::query()->firstOrFail();

    $product = Product::create([
        'pro_code' => $code,
        'pro_name' => $name,
        'pro_und' => 'und',
        'pro_iva' => '0',
        'pro_cat_id' => Category::factory()->create()->id,
        'pro_bra_id' => Brand::factory()->create()->id,
    ]);

    if ($cost !== null) {
        CostHistory::create([
            'cost_pro_code' => $code,
            'cost_pro_cost' => $cost,
            'cost_pro_cost_ref' => $cost,
            'cost_valorcam' => 1,
            'cost_currency' => 'USD',
            'cost_date' => now()->subDays(30)->toDateString(),
            'cost_user_id' => $user->id,
        ]);
    }

    foreach ($prices ?? [] as [$priceDate, $priceUsd, $utility]) {
        PriceHistory::create([
            'price_pro_code' => $code,
            'price_utility' => $utility,
            'price_usd' => $priceUsd,
            'price_date' => $priceDate,
            'price_user_id' => $user->id,
        ]);
    }

    return $product;
}

test('la lista de precios pagina y filtra por búsqueda', function () {
    seedPricedProduct('VID-001', 'Vidrio Templado 4mm', 10.0, [[now()->toDateString(), 15.0, 20]]);
    seedPricedProduct('ALU-001', 'Perfil Aluminio 2m', 5.0, [[now()->toDateString(), 8.0, 30]]);

    $this->get('/inventory/list-prices')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/ListPrices/Index')
            ->has('rows.data', 2)
            ->has('categories')
            ->has('brands'));

    $this->get('/inventory/list-prices?q='.urlencode('TEMPLADO'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.pro_code', 'VID-001'));
});

test('calcula utilidad registrada, margen real y variación vs precio anterior', function () {
    seedPricedProduct('CALC-01', 'Producto Cálculo', 10.0, [
        [now()->subDays(10)->toDateString(), 12.0, 15],
        [now()->subDays(2)->toDateString(), 15.0, 20],
    ]);

    $this->get('/inventory/list-prices')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('rows.data.0.last_price', fn (float|int $v) => abs((float) $v - 15.0) < 0.001)
            ->where('rows.data.0.last_utility', fn (float|int $v) => abs((float) $v - 20.0) < 0.001)
            ->where('rows.data.0.real_margin', fn (float|int $v) => abs((float) $v - 50.0) < 0.001)
            ->where('rows.data.0.variation_pct', fn (float|int $v) => abs((float) $v - 25.0) < 0.001));
});

test('filtra productos sin precio y con precio bajo costo', function () {
    seedPricedProduct('NOP-001', 'Producto Sin Precio', 10.0, null);
    seedPricedProduct('LOW-001', 'Producto Bajo Costo', 20.0, [
        [now()->toDateString(), 18.0, 10],
    ]);
    seedPricedProduct('OKL-001', 'Producto Normal', 10.0, [
        [now()->toDateString(), 25.0, 40],
    ]);

    $this->get('/inventory/list-prices?sin_precio=1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.pro_code', 'NOP-001'));

    $this->get('/inventory/list-prices?bajo_costo=1')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.pro_code', 'LOW-001'));
});

test('el endpoint de histórico devuelve la timeline fusionada ordenada', function () {
    seedPricedProduct('HIST-01', 'Producto Histórico', 9.0, [
        [now()->subDays(20)->toDateString(), 11.0, 18],
        [now()->subDay()->toDateString(), 14.0, 35],
    ]);

    CostHistory::create([
        'cost_pro_code' => 'HIST-01',
        'cost_pro_cost' => 9.0,
        'cost_pro_cost_ref' => 9.0,
        'cost_valorcam' => 1,
        'cost_currency' => 'USD',
        'cost_date' => now()->subDays(5)->toDateString(),
        'cost_user_id' => auth()->id(),
    ]);

    $response = $this->getJson('/api/products/HIST-01/price-history');

    $response->assertOk()
        ->assertJsonStructure([
            'product' => ['pro_code', 'pro_name'],
            'history' => [['type', 'date', 'amount', 'user']],
        ])
        ->assertJsonPath('product.pro_code', 'HIST-01');

    $history = $response->json('history');

    expect($history)->toHaveCount(4)
        ->and($history[0]['type'])->toBe('precio')
        ->and((float) $history[0]['amount'])->toBe(14.0)
        ->and($history[1]['type'])->toBe('costo')
        ->and($history[2]['amount'] ?? null)->toEqual(11.0)
        ->and(collect($history)->pluck('date')->toArray())->toEqual(
            collect($history)->pluck('date')->sortDesc()->values()->toArray(),
        );
});

test('el pdf de cliente responde como archivo pdf', function () {
    seedPricedProduct('PDF-001', 'Producto PDF', 10.0, [[now()->toDateString(), 16.0, 37.5]]);

    $response = $this->get('/inventory/list-prices/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf')
        ->and($response->headers->get('content-disposition'))->toContain('lista_precios');
});

test('ajustar precios conserva su contrato usando el repositorio sin n+1', function () {
    foreach (range(1, 6) as $i) {
        seedPricedProduct("ADJ-00{$i}", "Producto Ajuste {$i}", 10.0 + $i, [
            [now()->toDateString(), 20.0 + $i, 40],
        ]);
    }

    DB::enableQueryLog();

    $this->get('/inventory/adjust-prices')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/AdjustPrices')
            ->has('products', 6));

    expect(count(DB::getQueryLog()))->toBeLessThan(12);

    DB::disableQueryLog();
});
