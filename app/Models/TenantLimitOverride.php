<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Tenancy\TenantLimit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class TenantLimitOverride extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public static function valueFor(string $tenantId, TenantLimit|string $limit): ?int
    {
        $key = $limit instanceof TenantLimit ? $limit->value : $limit;

        $override = self::query()
            ->where('tenant_id', $tenantId)
            ->where('key', $key)
            ->first();

        return $override?->value;
    }
}
