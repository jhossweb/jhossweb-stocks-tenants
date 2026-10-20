<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Payments;

use App\Actions\Tenancy\RecordPaymentAction;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\TenantEvent;
use App\Models\User;
use Database\Seeders\Central\CentralAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithoutTenantProvisioning;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
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

    public function test_guest_cannot_access_payments_index(): void
    {
        $this->get(route('admin.payments.index'))->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_view_payments_index(): void
    {
        SubscriptionPayment::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('admin/payments/index')
                ->has('payments.data', 2)
                ->has('totalPaidUsd'));
    }

    public function test_record_payment_action_freezes_usd_equivalent(): void
    {
        $subscription = Subscription::factory()->create();

        $payment = app(RecordPaymentAction::class)->execute($subscription, [
            'amount' => 4200,
            'currency' => 'COP',
            'exchange_rate' => 4200,
            'method' => 'transfer',
            'paid_at' => now()->toDateString(),
        ]);

        expect($payment->receipt_number)->toBe('REC-0001')
            ->and((float) $payment->amount_usd)->toBe(1.0)
            ->and($payment->status)->toBe('paid');

        expect(TenantEvent::query()->where('event', 'payment_registered')->exists())->toBeTrue();
    }

    public function test_store_payment_creates_receipt_and_redirects_to_show(): void
    {
        $subscription = Subscription::factory()->create();

        $response = $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), [
                'subscription_id' => $subscription->id,
                'amount' => 100,
                'currency' => 'USD',
                'exchange_rate' => 1,
                'method' => 'transfer',
                'paid_at' => now()->toDateString(),
            ]);

        $payment = SubscriptionPayment::query()->firstOrFail();

        $response->assertRedirect(route('admin.payments.show', $payment));

        expect($payment->receipt_number)->toBe('REC-0001')
            ->and((float) $payment->amount_usd)->toBe(100.0);
    }

    public function test_store_payment_validates_payload(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.payments.store'), [])
            ->assertSessionHasErrors(['subscription_id', 'amount', 'currency', 'exchange_rate', 'method']);
    }

    public function test_void_marks_payment_as_voided(): void
    {
        $payment = SubscriptionPayment::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.payments.void', $payment), ['reason' => 'Error de carga'])
            ->assertRedirect(route('admin.payments.show', $payment));

        $payment->refresh();

        expect($payment->status)->toBe('voided')
            ->and($payment->voided_at)->not->toBeNull();

        expect(TenantEvent::query()->where('event', 'payment_voided')->exists())->toBeTrue();
    }

    public function test_receipt_downloads_a_pdf(): void
    {
        $payment = SubscriptionPayment::factory()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.payments.receipt', $payment))
            ->assertOk();

        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }
}
