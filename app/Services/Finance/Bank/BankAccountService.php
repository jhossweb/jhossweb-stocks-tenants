<?php

namespace App\Services\Finance\Bank;

use App\DTOs\Finance\Bank\BankAccountDTO;
use App\Models\BankAccount;
use App\Repositories\Finance\Bank\BankAccountRepository;
use Illuminate\Database\Eloquent\Collection;

class BankAccountService
{
    public function __construct(
        private readonly BankAccountRepository $repository,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function create(BankAccountDTO $dto): BankAccount
    {
        return $this->repository->create($dto);
    }

    public function update(BankAccount $bankAccount, BankAccountDTO $dto): bool
    {
        return $this->repository->update($bankAccount, $dto);
    }

    public function delete(BankAccount $bankAccount): bool
    {
        if ($this->repository->hasTransactions($bankAccount)) {
            return false;
        }

        return $this->repository->delete($bankAccount);
    }

    public function canDelete(BankAccount $bankAccount): bool
    {
        return ! $this->repository->hasTransactions($bankAccount);
    }
}
