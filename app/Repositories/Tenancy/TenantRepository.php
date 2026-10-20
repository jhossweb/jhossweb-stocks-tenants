<?php

declare(strict_types=1);

namespace App\Repositories\Tenancy;

use App\Enum\Tenancy\TenantStatus;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class TenantRepository
{
    /**
     * Columnas por las que se permite ordenar el listado administrativo.
     *
     * @var array<string, string>
     */
    private const SORTABLE = [
        'id' => 'id',
        'name' => 'name',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    /**
     * Query base del listado, con filtros y orden.
     *
     * @param  array{status?: string|null, search?: string|null}  $filters
     */
    public function query(array $filters = [], ?string $sort = null, string $direction = 'desc'): Builder
    {
        return $this->applyFilters(Tenant::query()->with('domains'), $filters)
            ->orderBy(self::SORTABLE[$sort] ?? 'created_at', $this->resolveDirection($direction));
    }

    /**
     * @param  array{status?: string|null, search?: string|null}  $filters
     * @return LengthAwarePaginator<int, Tenant>
     */
    public function paginate(array $filters = [], int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->query($filters, $sort, $direction)->paginate($perPage);
    }

    /**
     * @param  array{status?: string|null, search?: string|null}  $filters
     * @return Collection<int, Tenant>
     */
    public function all(array $filters = [], ?string $sort = null, string $direction = 'desc'): Collection
    {
        return $this->query($filters, $sort, $direction)->get();
    }

    /**
     * Tenants cuyo nombre, identificador o correo administrativo coincide con el término.
     *
     * @return Collection<int, Tenant>
     */
    public function search(string $term, int $limit = 20): Collection
    {
        return Tenant::query()
            ->with('domains')
            ->where(function (Builder $query) use ($term): void {
                $like = '%'.mb_strtolower($term).'%';
                $query->whereRaw('LOWER(id) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(data->>'admin_email', admin_email, '')) LIKE ?", [$like])
                    ->orWhereRaw('LOWER(COALESCE(rif, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(address, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', [$like]);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function findById(string $id): ?Tenant
    {
        return Tenant::query()->with('domains')->find($id);
    }

    /**
     * @param  array{status?: string|null, search?: string|null}  $filters
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status']) && $this->isValidStatus($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower((string) $filters['search']).'%';
            $query->where(function (Builder $query) use ($like): void {
                $query->whereRaw('LOWER(id) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw("LOWER(COALESCE(data->>'admin_email', admin_email, '')) LIKE ?", [$like])
                    ->orWhereRaw('LOWER(COALESCE(rif, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(address, "")) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(phone, "")) LIKE ?', [$like]);
            });
        }

        return $query;
    }

    private function isValidStatus(?string $status): bool
    {
        return $status !== null && TenantStatus::tryFrom($status) !== null;
    }

    private function resolveDirection(string $direction): string
    {
        return strtolower($direction) === 'asc' ? 'asc' : 'desc';
    }
}
