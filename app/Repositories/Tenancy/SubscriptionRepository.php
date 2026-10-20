<?php

declare(strict_types=1);

namespace App\Repositories\Tenancy;

use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SubscriptionRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Subscription>
     */
    public function query(array $filters = [], ?string $sort = null, string $direction = 'desc'): Builder
    {
        $query = Subscription::query()->with(['tenant', 'plan']);

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower((string) $filters['search']).'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->whereRaw('LOWER(tenant_id) LIKE ?', [$like])
                    ->orWhereHas('tenant', function (Builder $tenant) use ($like): void {
                        $tenant->whereRaw('LOWER(name) LIKE ?', [$like]);
                    })
                    ->orWhereHas('plan', function (Builder $plan) use ($like): void {
                        $plan->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['plan_id'])) {
            $query->where('plan_id', (int) $filters['plan_id']);
        }

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort ?? 'created_at', $direction);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Subscription>
     */
    public function paginate(array $filters = [], int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->query($filters, $sort, $direction)->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Subscription>
     */
    public function all(array $filters = [], ?string $sort = null, string $direction = 'desc'): Collection
    {
        return $this->query($filters, $sort, $direction)->get();
    }
}
