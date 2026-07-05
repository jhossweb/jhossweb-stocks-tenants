<?php

namespace App\Services\Sales\Customer;

use App\DTOs\Sales\Customer\CustomerDTO;
use App\Models\Customer;
use App\Repositories\Sales\Customer\CustomerRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $repository,
    ) {}

    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(CustomerDTO $dto): Customer
    {
        return $this->repository->create($dto);
    }

    public function update(Customer $customer, CustomerDTO $dto): bool
    {
        return $this->repository->update($customer, $dto);
    }

    public function delete(Customer $customer): bool
    {
        return $this->repository->delete($customer);
    }
}
