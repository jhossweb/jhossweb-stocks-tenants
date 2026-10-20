<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Rates;

use App\Models\BillingRate;
use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class BillingRateTest extends TestCase
{
    use RefreshDatabase;
    use WithoutTenantProvisioning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutTenantProvisioning();
        $this->seed(CentralAccessSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $user;
    }

    public function test_guest_cannot_access_rates_index(): void
    {
        $this->get(route('admin.rates.index'))->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_view_rates_index(): void
    {
        BillingRate::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.rates.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('admin/rates/index')
                ->has('rates.data', 3));
    }

    public function test_store_creates_a_rate(): void
    {
        $payload = [
            'rate_date' => now()->toDateString(),
            'rate_usd' => 40.5,
            'rate_cop' => 4200.1234,
            'rate_eur' => 0.92,
            'rate_cop_bs' => 0.01,
        ];

        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), $payload)
            ->assertRedirect(route('admin.rates.index'));

        $this->assertDatabaseHas('billing_rates', [
            'rate_date' => $payload['rate_date'],
        ]);
    }

    public function test_store_rejects_duplicate_date(): void
    {
        $rate = BillingRate::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.rates.store'), [
                'rate_date' => $rate->rate_date->toDateString(),
                'rate_usd' => 1,
                'rate_cop' => 1,
                'rate_eur' => 1,
                'rate_cop_bs' => 1,
            ])
            ->assertSessionHasErrors('rate_date');
    }

    public function test_update_modifies_a_rate(): void
    {
        $rate = BillingRate::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.rates.update', $rate), [
                'rate_date' => $rate->rate_date->toDateString(),
                'rate_usd' => 99.9,
                'rate_cop' => $rate->rate_cop,
                'rate_eur' => $rate->rate_eur,
                'rate_cop_bs' => $rate->rate_cop_bs,
            ])
            ->assertRedirect(route('admin.rates.index'));

        expect((float) $rate->refresh()->rate_usd)->toBe(99.9);
    }

    public function test_destroy_deletes_a_rate(): void
    {
        $rate = BillingRate::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.rates.destroy', $rate))
            ->assertRedirect(route('admin.rates.index'));

        $this->assertDatabaseMissing('billing_rates', ['id' => $rate->id]);
    }
}
