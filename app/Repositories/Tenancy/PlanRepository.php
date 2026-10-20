<?php

declare(strict_types=1);

namespace App\Repositories\Tenancy;

use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PlanRepository
{
    public function query(array $filters = [], ?string $sort = null, string $direction = 'desc'): Builder
    {
        $query = Plan::query();

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower((string) $filters['search']).'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->whereRaw('LOWER(code) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like]);
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort ?? 'created_at', $direction);

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, Plan>
     */
    public function paginate(array $filters = [], int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->query($filters, $sort, $direction)->paginate($perPage);
    }

    /**
     * @return Collection<int, Plan>
     */
    public function all(array $filters = [], ?string $sort = null, string $direction = 'desc'): Collection
    {
        return $this->query($filters, $sort, $direction)->get();
    }

    public function findById(int|string $id): ?Plan
    {
        return Plan::find($id);
    }

    public function findByCode(string $code): ?Plan
    {
        return Plan::where('code', $code)->first();
    }
}
