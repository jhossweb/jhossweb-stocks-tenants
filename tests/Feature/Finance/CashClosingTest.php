<?php

use App\Models\CashClosing;
use App\Models\Customer;
use App\Models\SaleOrder;
use App\Models\User;
use App\Repositories\Finance\CashClosingRepository;
use App\Services\Finance\CashClosingService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

function createCashClosingTestTables(): void
{
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('transaction_types')) {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->string('trans_type_code', 6)->primary();
            $table->string('trans_type_name', 100);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('customers')) {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('clt_name', 150);
            $table->string('clt_address', 200)->nullable();
            $table->string('clt_phone', 30)->nullable();
            $table->string('clt_email', 100)->nullable();
            $table->string('clt_website', 100)->nullable();
            $table->integer('clt_credit_days')->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('transaction_types')) {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->string('trans_type_code', 6)->primary();
            $table->string('trans_type_name', 100);
        });
    }

    if (! Schema::hasTable('sale_orders')) {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->string('sls_number', 50)->unique();
            $table->date('sls_date');
            $table->string('sls_trans_type_code', 6);
            $table->foreignId('sls_customer_id')->nullable()->constrained('customers');
            $table->decimal('sls_valorcam', 10, 4)->default(1);
            $table->string('sls_coin', 10)->default('USD');
            $table->decimal('sls_subtotal', 10, 4)->default(0);
            $table->decimal('sls_exento', 10, 4)->default(0);
            $table->decimal('sls_iva', 10, 4)->default(0);
            $table->decimal('sls_total', 10, 4)->default(0);
            $table->string('sls_status', 20)->default('pending');
            $table->string('sls_reference', 50)->nullable();
            $table->string('sls_payment_condition', 10)->default('cash');
            $table->integer('sls_credit_days')->default(0);
            $table->date('sls_due_date')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('cash_closings')) {
        Schema::create('cash_closings', function (Blueprint $table) {
            $table->id();
            $table->date('cc_date')->unique();
            $table->decimal('cc_opening_balance', 15, 4)->default(0);
            $table->decimal('cc_total_sales', 15, 4)->default(0);
            $table->decimal('cc_total_received', 15, 4)->default(0);
            $table->decimal('cc_total_manual_inflows', 15, 4)->default(0);
            $table->decimal('cc_total_manual_outflows', 15, 4)->default(0);
            $table->decimal('cc_expected_closing', 15, 4)->nullable();
            $table->decimal('cc_counted_amount', 15, 4)->nullable();
            $table->decimal('cc_difference', 15, 4)->nullable();
            $table->string('cc_status', 20)->default('balanced');
            $table->text('cc_notes')->nullable();
            $table->foreignId('cc_closed_by')->constrained('users');
            $table->timestamp('cc_closed_at')->nullable();
            $table->json('cc_counted_details')->nullable();
            $table->json('cc_differences')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('cash_movements')) {
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cm_cash_closing_id')->constrained('cash_closings')->cascadeOnDelete();
            $table->string('cm_type', 10);
            $table->decimal('cm_amount', 15, 4);
            $table->string('cm_description', 200);
            $table->string('cm_reference', 50)->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('rates')) {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->decimal('rate_value', 15, 4)->default(1);
            $table->string('rate_coin_from', 10)->default('USD');
            $table->string('rate_coin_to', 10)->default('USD');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('bank_accounts')) {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name', 150)->unique();
            $table->string('bank_account_number', 20)->unique();
            $table->string('bank_type');
            $table->string('bank_currency');
            $table->boolean('bank_status')->default(true);
            $table->string('bank_description', 150)->nullable();
            $table->decimal('bank_balance', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('bank_transactions')) {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('btr_bank_account_id')->constrained('bank_accounts');
            $table->string('btr_type');
            $table->decimal('btr_amount', 15, 4);
            $table->decimal('btr_balance', 15, 4)->default(0);
            $table->string('btr_reference', 50)->nullable();
            $table->string('btr_description', 150)->nullable();
            $table->decimal('btr_valorcam', 15, 4)->default(0);
            $table->decimal('btr_diferencial', 12, 4)->default(0);
            $table->string('btr_coin', 10)->nullable();
            $table->string('btr_method', 30)->nullable();
            $table->date('btr_transaction_date')->nullable();
            $table->string('btr_transactionable_type')->nullable();
            $table->bigInteger('btr_transactionable_id')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('accounts_receivable')) {
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->string('acr_sls_number', 50);
            $table->decimal('acr_total', 10, 4)->default(0);
            $table->decimal('acr_total_usd', 12, 4)->nullable();
            $table->decimal('acr_balance', 10, 4)->default(0);
            $table->decimal('acr_balance_usd', 12, 4)->nullable();
            $table->string('acr_status', 20)->default('pending');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('receipts')) {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rec_account_receivable_id')->constrained('accounts_receivable');
            $table->foreignId('rec_bank_transaction_id')->nullable()->constrained('bank_transactions');
            $table->decimal('rec_amount', 15, 4);
            $table->decimal('rec_diferencial', 12, 4)->default(0);
            $table->string('rec_type');
            $table->timestamps();
        });
    }
}

beforeEach(function () {
    createCashClosingTestTables();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $repo = new CashClosingRepository;
    $this->service = new CashClosingService($repo);

    if (Schema::hasTable('transaction_types') && DB::table('transaction_types')->count() === 0) {
        DB::table('transaction_types')->insert([
            'trans_type_code' => 'FACV',
            'trans_type_name' => 'Factura Venta',
        ]);
    }
});

test('preview returns zero when no sales or payments', function () {
    $preview = $this->service->calculatePreview(now()->format('Y-m-d'), 0);

    expect($preview['cc_opening_balance'])->toEqual(0.0);
    expect($preview['cc_total_sales'])->toEqual(0.0);
    expect($preview['cc_total_received'])->toEqual(0.0);
    expect($preview['cc_expected_closing'])->toEqual(0.0);
});

function createCashSale(string $number, string $date, int $customerId, float $total, string $status = 'paid'): void
{
    SaleOrder::withoutEvents(function () use ($number, $date, $customerId, $total, $status) {
        SaleOrder::factory()->create([
            'sls_number' => $number,
            'sls_date' => $date,
            'sls_trans_type_code' => 'FACV',
            'sls_customer_id' => $customerId,
            'sls_payment_condition' => 'cash',
            'sls_total' => $total,
            'sls_status' => $status,
        ]);
    });
}

test('preview calculates sales total correctly', function () {
    $date = now()->format('Y-m-d');

    $customer = Customer::factory()->create(['clt_name' => 'Test Customer']);

    createCashSale('INV-001', $date, $customer->id, 500, 'paid');
    createCashSale('INV-002', $date, $customer->id, 300, 'paid');

    $preview = $this->service->calculatePreview($date, 100);

    expect($preview['cc_opening_balance'])->toEqual(100.0);
    expect($preview['cc_total_sales'])->toEqual(800.0);
    expect($preview['cc_expected_closing'])->toBeNull();
});

test('preview excludes credit sales', function () {
    $date = now()->format('Y-m-d');

    $customer = Customer::factory()->create(['clt_name' => 'Test Customer']);

    SaleOrder::withoutEvents(function () use ($date, $customer) {
        SaleOrder::factory()->create([
            'sls_number' => 'INV-003',
            'sls_date' => $date,
            'sls_trans_type_code' => 'FACV',
            'sls_customer_id' => $customer->id,
            'sls_payment_condition' => 'credit',
            'sls_total' => 1000,
            'sls_status' => 'pending',
            'sls_credit_days' => 30,
            'sls_due_date' => now()->addDays(30),
        ]);
    });

    $preview = $this->service->calculatePreview($date, 0);

    expect($preview['cc_total_sales'])->toEqual(0.0);
    expect($preview['cc_expected_closing'])->toEqual(0.0);
});

test('excludes cancelled sales from preview', function () {
    $date = now()->format('Y-m-d');

    $customer = Customer::factory()->create(['clt_name' => 'Test Customer']);

    createCashSale('INV-004', $date, $customer->id, 500, 'cancelled');

    $preview = $this->service->calculatePreview($date, 0);

    expect($preview['cc_total_sales'])->toEqual(0.0);
});

test('store creates closing record with correct calculations', function () {
    $date = now()->format('Y-m-d');

    $customer = Customer::factory()->create(['clt_name' => 'Test Customer']);

    createCashSale('INV-005', $date, $customer->id, 600, 'paid');

    $movements = [
        ['cm_type' => 'inflow', 'cm_amount' => 50, 'cm_description' => 'Pago de deuda anterior', 'cm_reference' => null],
        ['cm_type' => 'outflow', 'cm_amount' => 30, 'cm_description' => 'Compra de agua', 'cm_reference' => null],
    ];

    $this->service->storeClosing(
        date: $date,
        openingBalance: 100,
        notes: 'Cierre del día',
        movementsData: $movements,
        user: $this->user,
        countedDetails: [
            ['coin' => 'USD', 'method' => 'EFECTIVO', 'counted' => 720],
        ],
        differences: ['USD' => 0],
    );

    expect(CashClosing::count())->toBe(1);

    $record = CashClosing::first();
    expect($record->cc_opening_balance)->toEqual(100.0);
    expect($record->cc_total_sales)->toEqual(600.0);
    expect($record->cc_total_manual_inflows)->toEqual(50.0);
    expect($record->cc_total_manual_outflows)->toEqual(30.0);
    expect($record->cc_expected_closing)->toBeNull();
    expect($record->cc_counted_amount)->toBeNull();
    expect($record->cc_difference)->toBeNull();
    expect($record->cc_status)->toBe('balanced');
    expect($record->cc_notes)->toBe('Cierre del día');
    expect($record->cc_closed_by)->toBe($this->user->id);
    expect($record->cc_closed_at)->not->toBeNull();
    expect($record->cc_counted_details)->toBeArray();
    expect($record->cc_counted_details[0]['coin'])->toBe('USD');
    expect($record->cc_counted_details[0]['counted'])->toEqual(720);
    expect($record->cc_differences['USD'])->toEqual(0);
});

test('store marks discrepancy when counted differs from expected', function () {
    $date = now()->format('Y-m-d');

    $this->service->storeClosing(
        date: $date,
        openingBalance: 0,
        notes: null,
        movementsData: [],
        user: $this->user,
        countedDetails: [
            ['coin' => 'USD', 'method' => 'EFECTIVO', 'counted' => 950],
        ],
        differences: ['USD' => 950],
    );

    $record = CashClosing::first();
    expect($record->cc_difference)->toBeNull();
    expect($record->cc_status)->toBe('discrepancy');
    expect($record->cc_differences['USD'])->toEqual(950);
});

test('prevents duplicate closing for same date', function () {
    $date = now()->format('Y-m-d');

    CashClosing::create([
        'cc_date' => $date,
        'cc_opening_balance' => 0,
        'cc_total_sales' => 0,
        'cc_total_received' => 0,
        'cc_total_manual_inflows' => 0,
        'cc_total_manual_outflows' => 0,
        'cc_expected_closing' => 0,
        'cc_counted_amount' => 0,
        'cc_difference' => 0,
        'cc_status' => 'balanced',
        'cc_closed_by' => $this->user->id,
        'cc_closed_at' => now(),
    ]);

    $this->expectException(QueryException::class);

    CashClosing::create([
        'cc_date' => $date,
        'cc_opening_balance' => 100,
        'cc_total_sales' => 0,
        'cc_total_received' => 0,
        'cc_total_manual_inflows' => 0,
        'cc_total_manual_outflows' => 0,
        'cc_expected_closing' => 100,
        'cc_counted_amount' => 100,
        'cc_difference' => 0,
        'cc_status' => 'balanced',
        'cc_closed_by' => $this->user->id,
        'cc_closed_at' => now(),
    ]);
});

test('store creates manual movement records', function () {
    $date = now()->format('Y-m-d');

    $movements = [
        ['cm_type' => 'inflow', 'cm_amount' => 200, 'cm_description' => 'Préstamo', 'cm_reference' => 'REF-001'],
        ['cm_type' => 'outflow', 'cm_amount' => 50, 'cm_description' => 'Pago proveedor', 'cm_reference' => null],
    ];

    $this->service->storeClosing(
        date: $date,
        openingBalance: 0,
        notes: null,
        movementsData: $movements,
        user: $this->user,
        countedDetails: [
            ['coin' => 'USD', 'method' => 'EFECTIVO', 'counted' => 150],
        ],
        differences: ['USD' => 0],
    );

    $record = CashClosing::first();
    expect($record->movements)->toHaveCount(2);

    $inflow = $record->movements->firstWhere('cm_type', 'inflow');
    expect($inflow->cm_amount)->toEqual(200.0);
    expect($inflow->cm_description)->toBe('Préstamo');
    expect($inflow->cm_reference)->toBe('REF-001');

    $outflow = $record->movements->firstWhere('cm_type', 'outflow');
    expect($outflow->cm_amount)->toEqual(50.0);
    expect($outflow->cm_description)->toBe('Pago proveedor');
});

test('validate store request fails without required fields', function () {
    $response = $this->post(route('cash-closing.store'), []);

    $response->assertSessionHasErrors([
        'cc_date',
        'cc_counted_details',
        'cc_differences',
    ]);
});

test('validate store request fails with invalid movement data', function () {
    $data = [
        'cc_date' => now()->format('Y-m-d'),
        'cc_opening_balance' => 0,
        'cc_counted_amount' => 0,
        'movements' => [
            ['cm_type' => 'invalid_type', 'cm_amount' => -10, 'cm_description' => ''],
        ],
    ];

    $response = $this->post(route('cash-closing.store'), $data);

    $response->assertSessionHasErrors([
        'movements.0.cm_type',
        'movements.0.cm_amount',
        'movements.0.cm_description',
    ]);
});

test('findClosingByDate returns null for non-existent date', function () {
    $repo = new CashClosingRepository;
    $result = $repo->findClosingByDate('2099-01-01');

    expect($result)->toBeNull();
});
