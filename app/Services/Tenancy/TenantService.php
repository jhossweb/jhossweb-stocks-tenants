<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Enum\Tenancy\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tenant
    {
        $id = trim((string) ($data['id'] ?? ''));
        if ($id === '') {
            $id = $this->uniqueSlug((string) $data['name']);
        }

        $tenant = new Tenant([
            'id' => $id,
            'name' => (string) $data['name'],
            'status' => TenantStatus::ACTIVE->value,
            'plan' => $data['plan'] ?? null,
        ]);

        if (isset($data['admin_email'])) {
            $tenant->admin_email = (string) $data['admin_email'];
        }

        if (isset($data['admin_name'])) {
            $tenant->admin_name = (string) $data['admin_name'];
        }

        if (isset($data['rif'])) {
            $tenant->rif = (string) $data['rif'];
        }

        if (isset($data['address'])) {
            $tenant->address = (string) $data['address'];
        }

        if (isset($data['phone'])) {
            $tenant->phone = (string) $data['phone'];
        }

        if (isset($data['internal_notes'])) {
            $tenant->internal_notes = (string) $data['internal_notes'];
        }

        if (isset($data['trial_ends_at'])) {
            $tenant->trial_ends_at = Carbon::parse((string) $data['trial_ends_at']);
        }

        if (isset($data['admin_password'])) {
            $tenant->admin_password = Hash::make((string) $data['admin_password']);
        }

        $tenant->save();

        return $tenant;
    }

    /**
     * Crea el subdominio del tenant si aún no existe, de modo que el cliente
     * reciba su enlace de acceso una vez dado de alta.
     */
    public function ensureDomain(Tenant $tenant): void
    {
        $suffix = (string) config('tenancy.tenant_domain_suffix');
        $domain = $tenant->id.'.'.$suffix;

        if ($tenant->domains()->where('domain', $domain)->exists()) {
            return;
        }

        $tenant->createDomain($domain);
        $tenant->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        $attributes = [];

        if (Arr::has($data, 'name')) {
            $attributes['name'] = (string) $data['name'];
        }

        if (Arr::has($data, 'plan')) {
            $tenant->plan = $data['plan'] === null ? null : (string) $data['plan'];
        }

        if (Arr::has($data, 'rif')) {
            $tenant->rif = $data['rif'] === null ? null : (string) $data['rif'];
        }

        if (Arr::has($data, 'admin_email')) {
            $tenant->admin_email = $data['admin_email'] === null ? null : (string) $data['admin_email'];
        }

        if (Arr::has($data, 'admin_name')) {
            $tenant->admin_name = $data['admin_name'] === null ? null : (string) $data['admin_name'];
        }

        if (Arr::has($data, 'internal_notes')) {
            $tenant->internal_notes = $data['internal_notes'] === null ? null : (string) $data['internal_notes'];
        }

        if (Arr::has($data, 'trial_ends_at')) {
            $tenant->trial_ends_at = $data['trial_ends_at'] === null ? null : Carbon::parse((string) $data['trial_ends_at']);
        }

        $tenant->forceFill($attributes);
        $tenant->save();

        return $tenant->refresh();
    }

    public function changeStatus(Tenant $tenant, string $status, ?string $reason = null, ?int $userId = null): Tenant
    {
        $current = TenantStatus::tryFrom((string) $tenant->status) ?? TenantStatus::PENDING;
        $target = TenantStatus::from($status);

        $this->validateStatusTransition($current, $target);

        if ($target === TenantStatus::SUSPENDED) {
            return $this->suspend($tenant, $reason, $userId);
        }

        if ($target === TenantStatus::ACTIVE) {
            return $this->activate($tenant);
        }

        if ($target === TenantStatus::PENDING) {
            $tenant->status = $target->value;
            $tenant->status_source = 'manual';
            $tenant->suspended_reason = null;
            $tenant->suspended_at = null;
            $tenant->suspended_by = null;
            $tenant->save();

            return $tenant->refresh();
        }

        return $tenant->refresh();
    }

    public function activate(Tenant $tenant): Tenant
    {
        $tenant->status = TenantStatus::ACTIVE->value;
        $tenant->status_source = 'manual';
        $tenant->suspended_reason = null;
        $tenant->suspended_at = null;
        $tenant->suspended_by = null;
        $tenant->save();

        return $tenant->refresh();
    }

    public function suspend(Tenant $tenant, ?string $reason = null, ?int $userId = null): Tenant
    {
        if ($reason === null || trim($reason) === '') {
            throw new \InvalidArgumentException('Suspension reason is required.');
        }

        $tenant->status = TenantStatus::SUSPENDED->value;
        $tenant->status_source = 'manual';
        $tenant->suspended_reason = trim($reason);
        $tenant->suspended_at = Carbon::now();
        $tenant->suspended_by = $userId;
        $tenant->save();

        return $tenant->refresh();
    }

    private function validateStatusTransition(TenantStatus $current, TenantStatus $target): void
    {
        $allowed = match ($current) {
            TenantStatus::PENDING => [TenantStatus::ACTIVE, TenantStatus::SUSPENDED],
            TenantStatus::ACTIVE => [TenantStatus::SUSPENDED],
            TenantStatus::SUSPENDED => [TenantStatus::ACTIVE],
        };

        if (! in_array($target, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid transition from {$current->value} to {$target->value}");
        }
    }

    /**
     * Genera un slug único para el id del tenant a partir de su nombre.
     */
    private function uniqueSlug(string $name): string
    {
        $suffix = (string) config('tenancy.tenant_domain_suffix');
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;
        $counter = 2;

        while (
            Tenant::query()->whereKey($slug)->exists()
            || config('tenancy.domain_model')::query()
                ->where('domain', $slug.'.'.$suffix)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
