<?php

namespace App\Repositories\Purchases\Supplier;

use App\DTOs\Purchases\Supplier\SupplierDTO;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Supplier::orderBy('supplier_name')->paginate($perPage);
    }

    public function create(SupplierDTO $dto): Supplier
    {
        return Supplier::create($dto->toArray());
    }

    public function update(Supplier $supplier, SupplierDTO $dto): bool
    {
        return $supplier->update($dto->toArray());
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }
}
