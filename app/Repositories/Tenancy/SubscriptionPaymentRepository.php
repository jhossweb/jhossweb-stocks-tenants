<?php

declare(strict_types=1);

namespace App\Repositories\Tenancy;

use App\Enum\Tenancy\PaymentStatus;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SubscriptionPaymentRepository
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<SubscriptionPayment>
     */
    public function query(array $filters = [], ?string $sort = null, string $direction = 'desc'): Builder
    {
        $query = SubscriptionPayment::query()->with(['subscription.plan', 'tenant', 'recordedBy']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['subscription_id'])) {
            $query->where('subscription_id', (int) $filters['subscription_id']);
        }

        if (! empty($filters['search'])) {
            $like = '%'.mb_strtolower((string) $filters['search']).'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->whereRaw('LOWER(receipt_number) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(reference, "")) LIKE ?', [$like])
                    ->orWhereHas('tenant', function (Builder $tenant) use ($like): void {
                        $tenant->whereRaw('LOWER(name) LIKE ?', [$like]);
                    });
            });
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort ?? 'created_at', $direction);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SubscriptionPayment>
     */
    public function paginate(array $filters = [], int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        return $this->query($filters, $sort, $direction)->paginate($perPage);
    }

    public function findById(int|string $id): ?SubscriptionPayment
    {
        return SubscriptionPayment::query()
            ->with(['subscription.plan', 'tenant', 'recordedBy', 'voidedBy'])
            ->find($id);
    }

    /**
     * Total cobrado en USD (solo pagos `paid`), excluyendo anulados.
     */
    public function totalPaidUsd(): float
    {
        return (float) SubscriptionPayment::query()
            ->where('status', PaymentStatus::PAID->value)
            ->sum('amount_usd');
    }
}
