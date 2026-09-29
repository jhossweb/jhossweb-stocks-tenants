<?php

declare(strict_types=1);

namespace App\Jobs\Tenant;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Se ejecuta en el pipeline de TenantCreated (después de migrar el schema).
 * Crea los datos iniciales del tenant a partir de la columna `data`:
 * fila de company + usuario admin verificado con rol 'admin' de spatie.
 *
 * Recibe el tenant por CONSTRUCTOR (patrón de los jobs de stancl/tenancy):
 * JobPipeline hace `new ProvisionTenantData($tenant)` y luego
 * `app()->call($handle)` — con `handle(Tenant $tenant)` el contenedor
 * resolvería un Tenant vacío y el job haría no-op en silencio.
 */
class ProvisionTenantData
{
    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $tenant = $this->tenant;

        // Tras create/save el trait VirtualColumn guarda los campos dentro de
        // `data`; al re-hidratar (queue/serialización) los vuelve atributos.
        // Se leen ambas formas para cubrir los dos estados.
        $payload = is_array($tenant->data) ? $tenant->data : [];

        $companyName = $payload['company_name'] ?? $tenant->name;
        $adminName = $payload['admin_name'] ?? $tenant->getAttribute('admin_name');
        $adminEmail = $payload['admin_email'] ?? $tenant->getAttribute('admin_email');
        $adminPasswordHash = $payload['admin_password'] ?? $tenant->getAttribute('admin_password');

        if ($companyName === null && $adminEmail === null) {
            return;
        }

        $tenant->run(function () use ($companyName, $adminName, $adminEmail, $adminPasswordHash): void {
            if ($companyName !== null && ! Company::query()->exists()) {
                // cmp_rif es NOT NULL sin default: el alta SaaS no lo captura,
                // se deja un placeholder editable desde el módulo de empresa.
                Company::create(['cmp_name' => $companyName, 'cmp_rif' => 'SIN-RIF']);
                Company::forgetCache();
            }

            if ($adminEmail !== null && $adminPasswordHash !== null && ! User::query()->where('email', $adminEmail)->exists()) {
                $role = Role::findOrCreate('admin', 'web');
                $user = User::create([
                    'name' => $adminName ?? 'Administrador',
                    'email' => $adminEmail,
                    // Ya viene hasheado: el cast 'hashed' de User no lo re-hashea.
                    'password' => $adminPasswordHash,
                    'email_verified_at' => now(),
                ]);
                $user->assignRole($role);
            }
        });
    }
}
