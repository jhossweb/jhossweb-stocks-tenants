<?php

namespace App\Repositories\Settings;

use App\DTOs\Settings\RateDTO;
use App\Models\Rate;
use Illuminate\Pagination\LengthAwarePaginator;

class RateRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Rate::orderBy('rate_date', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Rate
    {
        return Rate::find($id);
    }

    public function create(RateDTO $dto): Rate
    {
        return Rate::create($dto->toArray());
    }

    public function update(Rate $rate, RateDTO $dto): bool
    {
        return $rate->update($dto->toArray());
    }

    public function delete(Rate $rate): bool
    {
        return $rate->delete();
    }
}
