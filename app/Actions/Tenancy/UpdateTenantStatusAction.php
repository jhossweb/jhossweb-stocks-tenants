<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\DTOs\Tenancy\TenantStatusData;
use App\Models\Tenant;
use App\Models\TenantEvent;
use Illuminate\Support\Facades\Auth;

class UpdateTenantStatusAction
{
    public function execute(Tenant $tenant, TenantStatusData $data): Tenant
    {
        $previousStatus = $tenant->status;

        if ($data->status === 'suspended') {
            $tenant->status = 'suspended';
            $tenant->status_source = 'manual';
            $tenant->suspended_reason = $data->suspended_reason;
            $tenant->suspended_at = now();
            $tenant->suspended_by = $data->userId ?? Auth::id();
        } elseif ($data->status === 'active') {
            if ($tenant->hasManualSuspension() && $previousStatus === 'suspended') {
                // Mantener suspensión manual; no auto-activar
            } else {
                $tenant->status = 'active';
            }
        } else {
            $tenant->status = $data->status;
        }

        $tenant->save();

        TenantEvent::create([
            'tenant_id' => $tenant->id,
            'user_id' => $data->userId ?? Auth::id(),
            'event' => 'status_changed',
            'description' => "Estado cambió de {$previousStatus} a {$tenant->status}",
            'meta' => [
                'previous_status' => $previousStatus,
                'new_status' => $tenant->status,
                'reason' => $data->suspended_reason,
            ],
        ]);

        return $tenant;
    }
}
