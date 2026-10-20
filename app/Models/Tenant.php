<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\Tenancy\TenantStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @method static TenantFactory factory()
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    /** @use HasFactory<TenantFactory> */
    use HasDatabase, HasDomains, HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Columnas reales de la tabla `tenants`. Todo atributo que no esté aquí
     * se serializa en la columna `data` (VirtualColumn).
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'status',
            'plan',
            'rif',
            'address',
            'phone',
            'admin_email',
            'admin_name',
            'trial_ends_at',
            'internal_notes',
            'status_source',
            'suspended_reason',
            'suspended_at',
            'suspended_by',
            'schema_version',
        ];
    }

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
            'suspended_by' => 'integer',
        ];
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeByStatus(Builder $query, TenantStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * @param  Builder<Tenant>  $query
     * @return Builder<Tenant>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.mb_strtolower($term).'%';

        return $query->where(function (Builder $query) use ($like): void {
            $query->whereRaw('LOWER(id) LIKE ?', [$like])
                ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                ->orWhereRaw("LOWER(data->>'admin_email') LIKE ?", [$like]);
        });
    }

    public function statusEnum(): ?TenantStatus
    {
        return TenantStatus::tryFrom((string) $this->status);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isPending(): bool
    {
        return $this->status === TenantStatus::PENDING->value;
    }

    /**
     * Una suspensión manual nunca debe ser sobrescrita por la lógica de suscripciones.
     */
    public function hasManualSuspension(): bool
    {
        return $this->status_source === 'manual';
    }

    public function trialHasEnded(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isPast();
    }

    /**
     * Primer dominio del tenant (o null).
     */
    public function domain(): ?string
    {
        return $this->domains()->value('domain');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()->whereIn('status', ['trialing', 'active', 'past_due'])->latest()->first();
    }
}
