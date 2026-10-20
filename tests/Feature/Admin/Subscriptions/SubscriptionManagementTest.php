<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Subscriptions;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantEvent;
use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
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

    public function test_guest_cannot_access_subscriptions_index(): void
    {
        $this->get(route('admin.subscriptions.index'))->assertRedirect('/admin/login');
    }

    public function test_user_without_admin_access_cannot_access_subscriptions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.subscriptions.index'))
            ->assertStatus(403);
    }

    public function test_super_admin_can_view_subscriptions_index(): void
    {
        Subscription::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.subscriptions.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('admin/subscriptions/index')
                ->has('subscriptions.data', 2)
                ->has('statuses'));
    }

    public function test_store_creates_subscription_and_activates_tenant(): void
    {
        $tenant = Tenant::factory()->pending()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.store'), [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'grace_days' => 5,
            ])
            ->assertRedirect(route('admin.subscriptions.index'));

        $subscription = Subscription::query()->where('tenant_id', $tenant->id)->firstOrFail();

        expect($subscription->plan_id)->toBe($plan->id)
            ->and($subscription->status)->toBe('active')
            ->and($tenant->refresh()->status)->toBe('active');

        $this->assertDatabaseHas('tenant_events', [
            'tenant_id' => $tenant->id,
            'event' => 'plan_assigned',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.store'), [])
            ->assertSessionHasErrors(['tenant_id', 'plan_id']);
    }

    public function test_renew_extends_current_period(): void
    {
        $subscription = Subscription::factory()->create([
            'current_period_end' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.subscriptions.renew', $subscription))
            ->assertRedirect(route('admin.subscriptions.index'));

        expect($subscription->refresh()->current_period_end->toDateString())
            ->toBe(now()->addDays(10)->addMonth()->toDateString());
    }

    public function test_destroy_deletes_subscription(): void
    {
        $subscription = Subscription::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.subscriptions.destroy', $subscription))
            ->assertRedirect(route('admin.subscriptions.index'));

        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    public function test_assign_plan_from_tenant_show_creates_subscription(): void
    {
        $tenant = Tenant::factory()->pending()->create();
        $plan = Plan::factory()->create(['trial_days' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.tenants.assign-plan', $tenant), ['plan_id' => $plan->id])
            ->assertRedirect(route('admin.tenants.show', $tenant));

        $this->assertDatabaseHas('subscriptions', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
        ]);

        expect(TenantEvent::query()->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    }
}
