<?php

declare(strict_types=1);

namespace App\Repositories\Tenancy;

use App\Models\BillingRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class BillingRateRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<BillingRate>
     */
    public function query(array $filters = []): Builder
    {
        $query = BillingRate::query();

        if (! empty($filters['search'])) {
            $query->whereDate('rate_date', (string) $filters['search']);
        }

        return $query->orderBy('rate_date', 'desc');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, BillingRate>
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @return Collection<int, BillingRate>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function findById(int|string $id): ?BillingRate
    {
        return BillingRate::find($id);
    }

    public function latest(): ?BillingRate
    {
        return BillingRate::latestRate();
    }
}
