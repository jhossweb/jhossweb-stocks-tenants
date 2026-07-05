<?php

namespace App\Services\Settings;

use App\DTOs\Settings\RateDTO;
use App\Models\Rate;
use App\Repositories\Settings\RateRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RateService
{
    public function __construct(
        private readonly RateRepository $repository,
    ) {}

    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function findById(int $id): ?Rate
    {
        return $this->repository->findById($id);
    }

    public function create(RateDTO $dto): Rate
    {
        return $this->repository->create($dto);
    }

    public function update(Rate $rate, RateDTO $dto): bool
    {
        return $this->repository->update($rate, $dto);
    }

    public function delete(Rate $rate): bool
    {
        return $this->repository->delete($rate);
    }
}
