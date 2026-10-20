<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\DTOs\Tenant\RegisterTenantData;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTenant
{
    public function handle(RegisterTenantData $data): Tenant
    {
        $id = $data->slug ?? $this->uniqueSlug($data->companyName);

        // El trait VirtualColumn re-construye la columna `data` desde los
        // atributos no-CustomColumns: NUNCA pasar `data` en el array de create.
        // Los campos de provisioning se asignan como atributos y se serializan
        // solos dentro de `data`.
        $tenant = new Tenant([
            'id' => $id,
            'name' => $data->companyName,
            'status' => $data->status ?? Tenant::STATUS_ACTIVE,
        ]);
        $tenant->plan = $data->plan;
        $tenant->admin_name = $data->adminName;
        $tenant->admin_email = $data->adminEmail;
        // Hash almacenado en el esquema central; el job de provisioning lo usa
        // tal cual (el cast 'hashed' de User no re-hashea).
        $tenant->admin_password = Hash::make($data->adminPassword);
        // El subdominio lo crea TenantObserver::created en la propia save().
        $tenant->save();

        return $tenant;
    }

    private function uniqueSlug(string $companyName): string
    {
        $suffix = (string) config('tenancy.tenant_domain_suffix');
        $base = Str::slug($companyName) ?: 'empresa';
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
