<?php

namespace App\Repositories\Sales\Customer;

use App\DTOs\Sales\Customer\CustomerDTO;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return Customer::orderBy('clt_name')->paginate($perPage);
    }

    public function create(CustomerDTO $dto): Customer
    {
        return Customer::create($dto->toArray());
    }

    public function update(Customer $customer, CustomerDTO $dto): bool
    {
        return $customer->update($dto->toArray());
    }

    public function delete(Customer $customer): bool
    {
        return $customer->delete();
    }
}
