<?php

declare(strict_types=1);

use App\Enum\Tenancy\LimitScope;
use App\Enum\Tenancy\TenantLimit;
use App\Http\Requests\Rules\WithinPlanLimit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SaleOrder;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantLimitOverride;
use App\Models\TransactionType;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Tenancy\PlanUsageCounter;
use App\Services\Tenancy\UsageLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\WithoutTenantProvisioning;

uses(RefreshDatabase::class, WithoutTenantProvisioning::class);

function bindTenantWithLimits(array $limits, array $overrides = []): Tenant
{
    $plan = Plan::factory()->create(['limits' => $limits]);
    $tenant = Tenant::factory()->create();

    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    foreach ($overrides as $key => $value) {
        TenantLimitOverride::create([
            'tenant_id' => $tenant->id,
            'key' => $key,
            'value' => $value,
        ]);
    }

    app()->instance('tenant', $tenant);

    return $tenant;
}

beforeEach(function (): void {
    $this->withoutTenantProvisioning();

    foreach (['FACV', 'COT', 'DEVV', 'DEVC'] as $code) {
        TransactionType::firstOrCreate(
            ['trans_type_code' => $code],
            [
                'trans_type_description' => $code,
                'trans_type_factor' => in_array($code, ['FACV', 'DEVC'], true) ? -1 : 1,
            ],
        );
    }
});

it('counts monthly sales documents by created_at', function (): void {
    bindTenantWithLimits([]);

    SaleOrder::factory()->count(2)->create([
        'sls_trans_type_code' => 'COT',
        'created_at' => now(),
    ]);
    SaleOrder::factory()->create([
        'sls_trans_type_code' => 'COT',
        'created_at' => now()->subMonthNoOverflow(),
    ]);
    SaleOrder::factory()->create([
        'sls_trans_type_code' => 'FACV',
        'created_at' => now(),
    ]);
    SaleOrder::factory()->create([
        'sls_trans_type_code' => 'DEVV',
        'created_at' => now(),
    ]);

    $counter = app(PlanUsageCounter::class);

    expect($counter->count(TenantLimit::SalesQuotesMonthly))->toBe(2)
        ->and($counter->count(TenantLimit::SalesInvoicesMonthly))->toBe(1)
        ->and($counter->count(TenantLimit::SalesReturnsMonthly))->toBe(1);
});

it('counts total entities', function (): void {
    bindTenantWithLimits([]);

    Customer::factory()->count(2)->create();
    Supplier::factory()->create();
    Warehouse::factory()->create();
    Category::factory()->create();
    Brand::factory()->create();
    Product::factory()->create();

    $counter = app(PlanUsageCounter::class);

    expect($counter->count(TenantLimit::CustomersMaxTotal))->toBe(2)
        ->and($counter->count(TenantLimit::SuppliersMaxTotal))->toBe(1)
        ->and($counter->count(TenantLimit::WarehousesMaxTotal))->toBe(1)
        ->and($counter->count(TenantLimit::ProductsMaxTotal))->toBe(1);
});

it('counts purchase returns monthly', function (): void {
    bindTenantWithLimits([]);
    Supplier::factory()->create();

    PurchaseOrder::factory()->create([
        'invoice_trans_type_code' => 'DEVC',
        'created_at' => now(),
    ]);
    PurchaseOrder::factory()->create([
        'invoice_trans_type_code' => 'DEVC',
        'created_at' => now()->subMonthNoOverflow(),
    ]);

    expect(app(PlanUsageCounter::class)->count(TenantLimit::PurchasesReturnsMonthly))->toBe(1);
});

it('enforces limits and gives priority to tenant overrides', function (): void {
    $tenant = bindTenantWithLimits([
        'sales.quotes_monthly' => 1,
        'products.max_total' => 2,
    ]);

    SaleOrder::factory()->create([
        'sls_trans_type_code' => 'COT',
        'created_at' => now(),
    ]);

    $limiter = app(UsageLimiter::class);

    expect($limiter->getLimit(TenantLimit::SalesQuotesMonthly))->toBe(1)
        ->and($limiter->canCreate(TenantLimit::SalesQuotesMonthly))->toBeFalse();

    TenantLimitOverride::create([
        'tenant_id' => $tenant->id,
        'key' => 'sales.quotes_monthly',
        'value' => 5,
    ]);

    expect($limiter->getLimit(TenantLimit::SalesQuotesMonthly))->toBe(5)
        ->and($limiter->canCreate(TenantLimit::SalesQuotesMonthly))->toBeTrue();

    TenantLimitOverride::where('tenant_id', $tenant->id)
        ->where('key', 'sales.quotes_monthly')
        ->update(['value' => 0]);

    expect($limiter->getLimit(TenantLimit::SalesQuotesMonthly))->toBe(0)
        ->and($limiter->canCreate(TenantLimit::SalesQuotesMonthly))->toBeFalse();
});

it('treats missing keys and missing tenant as unlimited', function (): void {
    bindTenantWithLimits([]);

    $limiter = app(UsageLimiter::class);

    expect($limiter->getLimit(TenantLimit::ProductsMaxTotal))->toBeNull()
        ->and($limiter->canCreate(TenantLimit::ProductsMaxTotal))->toBeTrue();

    app()->forgetInstance('tenant');

    expect($limiter->getLimit(TenantLimit::ProductsMaxTotal))->toBeNull()
        ->and($limiter->canCreate(TenantLimit::ProductsMaxTotal))->toBeTrue();
});

it('fails validation when the plan limit is reached', function (): void {
    bindTenantWithLimits(['products.max_total' => 0]);

    $validator = Validator::make(
        ['pro_code' => 'P-001'],
        ['pro_code' => [new WithinPlanLimit(TenantLimit::ProductsMaxTotal)]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('pro_code'))->toContain('límite de Productos');
});

it('blocks creation through the form request rule', function (): void {
    bindTenantWithLimits(['customers.max_total' => 0]);

    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->postJson('/api/customers', [
            'clt_name' => 'ACME',
            'clt_address' => 'Street 1',
            'clt_phone' => '04140000000',
            'clt_email' => 'acme@example.com',
            'clt_credit_days' => 0,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['clt_name']);
});

it('exposes limit metadata grouped by module', function (): void {
    $groups = TenantLimit::grouped();

    $keys = collect($groups)->flatMap(fn (array $group) => $group['limits'])->pluck('key');
    $salesGroup = collect($groups)->firstWhere('key', 'sales');

    expect($keys)->toContain('sales.invoices_monthly', 'sales.quotes_monthly', 'sales.returns_monthly')
        ->and($salesGroup['limits'][0]['scope'])->toBe(LimitScope::Monthly->value)
        ->and(TenantLimit::ProductsMaxTotal->scope())->toBe(LimitScope::Total);
});
