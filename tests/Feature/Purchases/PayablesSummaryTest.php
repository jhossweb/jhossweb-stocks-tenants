<?php

use App\Enum\Purchases\StatusPurchases;
use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use App\Repositories\Purchases\AccountsPayable\AccountsPayableRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    TransactionType::create([
        'trans_type_code' => 'FAC',
        'trans_type_description' => 'Factura Compra',
        'trans_type_factor' => '1',
    ]);

    Rate::factory()->create([
        'rate_date' => now()->toDateString(),
        'rate_usd' => 795.0,
        'rate_cop' => 3200.0,
        'rate_eur' => 895.0,
        'rate_cop_bs' => 3.5,
    ]);

    // La factory de PurchaseOrder requiere al menos un proveedor existente
    Supplier::factory()->create();

    $this->repository = new AccountsPayableRepository;
});

function creditPurchase(Supplier $supplier, string $coin, float $total, $dueDate): PurchaseOrder
{
    return PurchaseOrder::factory()->create([
        'invoice_trans_type_code' => 'FAC',
        'invoice_supplier_id' => $supplier->id,
        'invoice_coin' => $coin,
        'invoice_total' => $total,
        'invoice_subtotal' => $total,
        'invoice_exento' => 0,
        'invoice_iva' => 0,
        'invoice_payment_condition' => 'credit',
        'invoice_credit_days' => 30,
        'invoice_date' => $dueDate->copy()->subDays(30)->toDateString(),
        'invoice_due_date' => $dueDate->toDateString(),
    ]);
}

it('agrupa las CxP por proveedor y moneda con tramos de antigüedad', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);
    $beta = Supplier::factory()->create(['supplier_name' => 'Beta Import']);

    creditPurchase($alpha, 'USD', 100, now()->addDays(10));
    creditPurchase($alpha, 'USD', 150, now()->subDays(20));
    creditPurchase($beta, 'COP', 70000, now()->subDays(100));

    // Factura pagada: debe excluirse del resumen
    $paid = creditPurchase($beta, 'COP', 9999, now()->addDays(5));
    $paid->accountsPayable->update([
        'acp_status' => StatusPurchases::PAID->value,
        'acp_balance' => 0,
    ]);

    $summaries = $this->repository->summaryBySupplier();

    expect($summaries)->toHaveCount(2);

    $alphaUsd = $summaries->first(fn ($s) => $s['supplier']['id'] === $alpha->id);
    expect($alphaUsd['coin'])->toBe('USD');
    expect($alphaUsd['documents_count'])->toBe(2);
    expect($alphaUsd['aging']['current'])->toBe(100.0);
    expect($alphaUsd['aging']['days_1_30'])->toBe(150.0);
    expect($alphaUsd['total_balance'])->toBe(250.0);

    $betaCop = $summaries->first(fn ($s) => $s['supplier']['id'] === $beta->id);
    expect($betaCop['coin'])->toBe('COP');
    expect($betaCop['documents_count'])->toBe(1);
    expect($betaCop['aging']['days_over_90'])->toBe(70000.0);
});

it('calcula días restantes en PurchaseOrder', function () {
    $future = PurchaseOrder::factory()->create([
        'invoice_date' => now()->toDateString(),
        'invoice_due_date' => now()->addDays(7)->toDateString(),
    ]);
    expect((int) $future->days_remaining)->toBe(7);

    $past = PurchaseOrder::factory()->create([
        'invoice_date' => now()->subDays(33)->toDateString(),
        'invoice_due_date' => now()->subDays(3)->toDateString(),
    ]);
    expect((int) $past->days_remaining)->toBe(-3);

    $today = PurchaseOrder::factory()->create([
        'invoice_date' => now()->toDateString(),
        'invoice_due_date' => now()->toDateString(),
    ]);
    expect((int) $today->days_remaining)->toBe(0);
});

it('filtra el resumen por búsqueda de proveedor o factura', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);
    $beta = Supplier::factory()->create(['supplier_name' => 'Beta Import']);

    $poA = creditPurchase($alpha, 'USD', 100, now()->addDays(10));
    creditPurchase($beta, 'USD', 300, now()->addDays(20));

    $byName = $this->repository->summaryBySupplier('Alpha');
    expect($byName)->toHaveCount(1);
    expect($byName[0]['supplier']['id'])->toBe($alpha->id);

    $byInvoice = $this->repository->summaryBySupplier($poA->invoice_number);
    expect($byInvoice)->toHaveCount(1);
    expect($byInvoice[0]['supplier']['id'])->toBe($alpha->id);
});

it('calcula el abono por documento y el total abonado del grupo', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);

    $full = creditPurchase($alpha, 'USD', 100, now()->addDays(10));
    $partial = creditPurchase($alpha, 'USD', 200, now()->addDays(20));

    // Simula un abono parcial de 150 sobre la segunda factura
    $partial->accountsPayable->update(['acp_balance' => 50]);

    $summaries = $this->repository->summaryBySupplier();
    $summary = $summaries->first(fn ($s) => $s['supplier']['id'] === $alpha->id);

    expect($summary['details'])->toHaveCount(2);

    $fullDetail = collect($summary['details'])->first(fn ($d) => $d['number'] === $full->invoice_number);
    expect($fullDetail['paid'])->toBe(0.0);
    expect($fullDetail['balance'])->toBe(100.0);

    $partialDetail = collect($summary['details'])->first(fn ($d) => $d['number'] === $partial->invoice_number);
    expect($partialDetail['paid'])->toBe(150.0);
    expect($partialDetail['balance'])->toBe(50.0);

    expect($summary['total_paid'])->toBe(150.0);
});

it('renderiza la página de CxP por proveedor', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);
    creditPurchase($alpha, 'USD', 500, now()->addDays(15));

    $this->get(route('accounts-payable.by-supplier'))
        ->assertOk();

    $this->get(route('accounts-payable.by-supplier', ['search' => 'Alpha']))
        ->assertOk();
});

it('mantiene el listado de CxP funcionando tras el refactor al repositorio', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);
    creditPurchase($alpha, 'USD', 500, now()->addDays(15));

    $this->get(route('accounts-payable.index'))
        ->assertOk();

    $this->get(route('accounts-payable.index', ['search' => 'Alpha']))
        ->assertOk();

    $this->get(route('accounts-payable.index', ['status' => StatusPurchases::PENDING->value]))
        ->assertOk();
});

it('descarga el estado de cuenta PDF del proveedor', function () {
    $alpha = Supplier::factory()->create(['supplier_name' => 'Alpha Supplies']);
    creditPurchase($alpha, 'USD', 500, now()->addDays(15));
    creditPurchase($alpha, 'COP', 80000, now()->subDays(40));

    $response = $this->get(route('accounts-payable.statement', $alpha));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
