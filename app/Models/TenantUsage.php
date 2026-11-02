<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * @deprecated Los límites se calculan en vivo con PlanUsageCounter.
 *             La tabla se conserva como legado para reportes futuros.
 */
class TenantUsage extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'key',
        'period',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
