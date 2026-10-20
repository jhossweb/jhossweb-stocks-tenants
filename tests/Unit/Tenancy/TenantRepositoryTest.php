<?php

declare(strict_types=1);

namespace Tests\Unit\Tenancy;

use App\Models\Tenant;
use App\Repositories\Tenancy\TenantRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class TenantRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenantProvisioning();
    }

    public function test_repository_filters_by_status(): void
    {
        Tenant::factory()->create(['status' => 'active']);
        Tenant::factory()->create(['status' => 'pending']);
        Tenant::factory()->create(['status' => 'suspended']);

        $repo = app(TenantRepository::class);
        $results = $repo->query(filters: ['status' => 'pending'])->get();

        $this->assertCount(1, $results);
        $this->assertSame('pending', $results->first()->status);
    }

    public function test_repository_searches_by_id_and_name(): void
    {
        Tenant::factory()->create(['id' => 'acme', 'data' => ['name' => 'Acme Corp']]);
        Tenant::factory()->create(['id' => 'beta', 'data' => ['name' => 'Beta LLC']]);

        $repo = app(TenantRepository::class);
        $byId = $repo->query(filters: ['search' => 'acme'])->get();
        $byName = $repo->query(filters: ['search' => 'beta'])->get();

        $this->assertCount(1, $byId);
        $this->assertCount(1, $byName);
    }

    public function test_repository_orders_and_paginated(): void
    {
        Tenant::factory()->create(['id' => 'zeta']);
        Tenant::factory()->create(['id' => 'alpha']);

        $repo = app(TenantRepository::class);
        $paginated = $repo->query(sort: 'id', direction: 'asc')->paginate(10);

        $this->assertSame('alpha', $paginated->items()[0]->getKey());
    }
}
