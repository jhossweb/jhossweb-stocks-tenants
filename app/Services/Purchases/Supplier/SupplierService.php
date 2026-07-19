<?php

namespace App\Services\Purchases\Supplier;

use App\DTOs\Purchases\Supplier\SupplierDTO;
use App\Models\Supplier;
use App\Repositories\Purchases\Supplier\SupplierRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepository $repository,
    ) {}

    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(SupplierDTO $dto): Supplier
    {
        return $this->repository->create($dto);
    }

    public function update(Supplier $supplier, SupplierDTO $dto): bool
    {
        return $this->repository->update($supplier, $dto);
    }

    public function delete(Supplier $supplier): bool
    {
        return $this->repository->delete($supplier);
    }
}
