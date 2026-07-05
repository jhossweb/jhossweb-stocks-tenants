<?php

namespace App\Repositories\Finance\Bank;

use App\DTOs\Finance\Bank\BankAccountDTO;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Collection;

class BankAccountRepository
{
    public function all(): Collection
    {
        return BankAccount::orderBy('bank_name')->get();
    }

    public function create(BankAccountDTO $dto): BankAccount
    {
        return BankAccount::create($dto->toArray());
    }

    public function update(BankAccount $bankAccount, BankAccountDTO $dto): bool
    {
        return $bankAccount->update($dto->toArray());
    }

    public function delete(BankAccount $bankAccount): bool
    {
        return $bankAccount->delete();
    }

    public function hasTransactions(BankAccount $bankAccount): bool
    {
        return $bankAccount->bankTransactions()->exists();
    }
}
