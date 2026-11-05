<?php

declare(strict_types=1);

use App\Models\SaleOrder;
use App\Models\TransactionType;
use App\Models\User;
use App\Services\Settings\TenantPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    TransactionType::firstOrCreate(
        ['trans_type_code' => 'FACV'],
        ['trans_type_description' => 'Factura Venta', 'trans_type_factor' => -1],
    );
    TransactionType::firstOrCreate(
        ['trans_type_code' => 'COT'],
        ['trans_type_description' => 'Cotización', 'trans_type_factor' => 0],
    );

    app(TenantPermissionService::class)->sync();
});

function lifecycleUser(string $permission): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo($permission);

    return $user;
}

it('responde con aviso, no con 500, al intentar anular una factura pagada', function (): void {
    $user = lifecycleUser('sales.invoices.void');

    $sale = SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-9001',
        'sls_status' => 'paid',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
        'sls_due_date' => now()->addDays(30)->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('sales.void', ['id' => $sale->id]), ['reason' => 'Motivo'])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($sale->refresh()->voided_at)->toBeNull();
});

it('anula una factura de venta sin pagos desde la ruta', function (): void {
    $user = lifecycleUser('sales.invoices.void');

    $sale = SaleOrder::factory()->facv()->create([
        'sls_number' => 'FACV-9002',
        'sls_status' => 'pending',
        'sls_payment_condition' => 'credit',
        'sls_credit_days' => 30,
        'sls_due_date' => now()->addDays(30)->toDateString(),
    ]);

    $this->actingAs($user)
        ->post(route('sales.void', ['id' => $sale->id]), ['reason' => 'Motivo válido'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($sale->refresh()->voided_at)->not->toBeNull();
});

it('inactiva una cotización sin error de parámetros', function (): void {
    $user = lifecycleUser('sales.quotes.deactivate');

    $quote = SaleOrder::factory()->create([
        'sls_number' => 'COT-9001',
        'sls_trans_type_code' => 'COT',
        'sls_quote_status' => 'pending',
    ]);

    $this->actingAs($user)
        ->patch(route('quotes.deactivate', ['id' => $quote->id]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($quote->refresh()->is_active)->toBeFalse();
});
