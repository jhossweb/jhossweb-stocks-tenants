<?php

namespace App\Services\Sales\Customer;

use App\DTOs\Sales\Customer\CustomerDTO;
use App\Enum\Tenancy\TenantLimit;
use App\Models\Customer;
use App\Repositories\Sales\Customer\CustomerRepository;
use App\Services\Tenancy\UsageLimiter;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $repository,
        private readonly UsageLimiter $usageLimiter,
    ) {}

    public function list(int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(CustomerDTO $dto): Customer
    {
        $this->usageLimiter->assertCanCreate(TenantLimit::CustomersMaxTotal);

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
