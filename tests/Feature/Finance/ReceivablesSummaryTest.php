<?php

use App\Enum\Sales\StatusSales;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use App\Repositories\Sales\AccountsReceivable\AccountsReceivableRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    TransactionType::create([
        'trans_type_code' => 'FACV',
        'trans_type_description' => 'Factura Venta',
        'trans_type_factor' => '-1',
    ]);

    Rate::factory()->create([
        'rate_date' => now()->toDateString(),
        'rate_usd' => 795.0,
        'rate_cop' => 3200.0,
        'rate_eur' => 895.0,
        'rate_cop_bs' => 3.5,
    ]);

    $this->repository = new AccountsReceivableRepository;
});

function creditSale(Customer $customer, string $coin, float $total, string $dueDate): SaleOrder
{
    return SaleOrder::factory()->facv()->create([
        'sls_customer_id' => $customer->id,
        'sls_coin' => $coin,
        'sls_total' => $total,
        'sls_subtotal' => $total,
        'sls_exento' => 0,
        'sls_iva' => 0,
        'sls_payment_condition' => 'credit',
        'sls_date' => now()->subYear()->toDateString(),
        'sls_due_date' => $dueDate,
    ]);
}

it('agrupa las CxC por cliente y moneda con tramos de antigüedad', function () {
    $alpha = Customer::factory()->create(['clt_name' => 'Alpha SA']);
    $beta = Customer::factory()->create(['clt_name' => 'Beta Ltda']);

    creditSale($alpha, 'USD', 100, now()->addDays(10)->toDateString());
    creditSale($alpha, 'USD', 200, now()->subDays(45)->toDateString());
    creditSale($beta, 'COP', 50000, now()->subDays(100)->toDateString());

    // Factura pagada: debe excluirse del resumen
    $paid = creditSale($beta, 'COP', 9999, now()->addDays(5)->toDateString());
    $paid->accountsReceivable->update([
        'acr_status' => StatusSales::PAID->value,
        'acr_balance' => 0,
    ]);

    $summaries = $this->repository->summaryByCustomer();

    expect($summaries)->toHaveCount(2);

    $alphaUsd = $summaries->first(fn ($s) => $s['customer']['id'] === $alpha->id);
    expect($alphaUsd['coin'])->toBe('USD');
    expect($alphaUsd['documents_count'])->toBe(2);
    expect($alphaUsd['aging']['current'])->toBe(100.0);
    expect($alphaUsd['aging']['days_31_60'])->toBe(200.0);
    expect($alphaUsd['aging']['days_over_90'])->toBe(0.0);
    expect($alphaUsd['total_balance'])->toBe(300.0);

    $betaCop = $summaries->first(fn ($s) => $s['customer']['id'] === $beta->id);
    expect($betaCop['coin'])->toBe('COP');
    expect($betaCop['documents_count'])->toBe(1);
    expect($betaCop['aging']['days_over_90'])->toBe(50000.0);
    expect($betaCop['total_balance'])->toBe(50000.0);
});

it('incluye el detalle de documentos en cada resumen', function () {
    $alpha = Customer::factory()->create(['clt_name' => 'Alpha SA']);

    creditSale($alpha, 'USD', 100, now()->addDays(10)->toDateString());
    creditSale($alpha, 'USD', 200, now()->subDays(45)->toDateString());

    $summaries = $this->repository->summaryByCustomer();
    $details = $summaries[0]['details'];

    expect($details)->toHaveCount(2);

    foreach ($details as $detail) {
        expect($detail)->toHaveKeys(['id', 'number', 'date', 'due_date', 'days_remaining', 'total', 'paid', 'balance']);
    }

    $current = collect($details)->first(fn ($d) => $d['days_remaining'] === 10);
    expect($current['balance'])->toBe(100.0);

    $overdue = collect($details)->first(fn ($d) => $d['days_remaining'] === -45);
    expect($overdue['balance'])->toBe(200.0);
});

it('filtra el resumen por búsqueda de cliente o factura', function () {
    $alpha = Customer::factory()->create(['clt_name' => 'Alpha SA']);
    $beta = Customer::factory()->create(['clt_name' => 'Beta Ltda']);

    $saleA = creditSale($alpha, 'USD', 100, now()->addDays(10)->toDateString());
    creditSale($beta, 'USD', 300, now()->addDays(20)->toDateString());

    $byName = $this->repository->summaryByCustomer('Alpha');
    expect($byName)->toHaveCount(1);
    expect($byName[0]['customer']['id'])->toBe($alpha->id);

    $byInvoice = $this->repository->summaryByCustomer($saleA->sls_number);
    expect($byInvoice)->toHaveCount(1);
    expect($byInvoice[0]['customer']['id'])->toBe($alpha->id);

    $all = $this->repository->summaryByCustomer();
    expect($all)->toHaveCount(2);
});

it('calcula el abono por documento y el total abonado del grupo', function () {
    $alpha = Customer::factory()->create(['clt_name' => 'Alpha SA']);

    $full = creditSale($alpha, 'USD', 100, now()->addDays(10)->toDateString());
    $partial = creditSale($alpha, 'USD', 200, now()->addDays(20)->toDateString());

    // Simula un abono parcial de 150 sobre la segunda factura
    $partial->accountsReceivable->update(['acr_balance' => 50]);

    $summaries = $this->repository->summaryByCustomer();
    $summary = $summaries->first(fn ($s) => $s['customer']['id'] === $alpha->id);

    expect($summary['details'])->toHaveCount(2);

    $fullDetail = collect($summary['details'])->first(fn ($d) => $d['number'] === $full->sls_number);
    expect($fullDetail['paid'])->toBe(0.0);
    expect($fullDetail['balance'])->toBe(100.0);

    $partialDetail = collect($summary['details'])->first(fn ($d) => $d['number'] === $partial->sls_number);
    expect($partialDetail['paid'])->toBe(150.0);
    expect($partialDetail['balance'])->toBe(50.0);

    expect($summary['total_paid'])->toBe(150.0);
});

it('renderiza la página de CxC por cliente', function () {
    $alpha = Customer::factory()->create(['clt_name' => 'Alpha SA']);
    creditSale($alpha, 'USD', 100, now()->addDays(10)->toDateString());

    $this->get(route('accounts-receivable.by-customer'))
        ->assertOk();

    $this->get(route('accounts-receivable.by-customer', ['search' => 'Alpha']))
        ->assertOk();
});
