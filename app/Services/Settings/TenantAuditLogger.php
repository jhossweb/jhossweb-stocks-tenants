<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\TenantAuditLog;
use Illuminate\Database\Eloquent\Model;

class TenantAuditLogger
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        string $event,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
    ): void {
        if (! tenancy()->initialized) {
            return;
        }

        try {
            $user = auth()->user();
            $request = request();

            TenantAuditLog::create([
                'user_id' => $user?->getAuthIdentifier(),
                'event' => $event,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'old_values' => $oldValues === [] ? null : $oldValues,
                'new_values' => $newValues === [] ? null : $newValues,
                'metadata' => $metadata === [] ? null : $metadata,
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // La auditoría nunca debe bloquear la operación principal.
        }
    }
}
