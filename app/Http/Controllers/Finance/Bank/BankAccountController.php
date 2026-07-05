<?php

namespace App\Http\Controllers\Finance\Bank;

use App\DTOs\Finance\Bank\BankAccountDTO;
use App\Enum\Finanzas\TypeAccount;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Bank\StoreBankAccountRequest;
use App\Http\Requests\Finance\Bank\UpdateBankAccountRequest;
use App\Models\BankAccount;
use App\Services\Finance\Bank\BankAccountService;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function __construct(
        private readonly BankAccountService $bankAccountService,
    ) {}

    public function index()
    {
        return Inertia::render('Finance/Bank/Index', [
            'bankAccounts' => $this->bankAccountService->all(),
            'types' => TypeAccount::values(),
            'currencies' => TypeCoin::values(),
        ]);
    }

    public function store(StoreBankAccountRequest $request)
    {
        $this->bankAccountService->create(BankAccountDTO::fromArray($request->validated()));

        return redirect()->back()->with('success', 'Cuenta bancaria creada exitosamente.');
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount)
    {
        $this->bankAccountService->update($bankAccount, BankAccountDTO::fromArray($request->validated()));

        return redirect()->back()->with('success', 'Cuenta bancaria actualizada exitosamente.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        if (! $this->bankAccountService->canDelete($bankAccount)) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar una cuenta con transacciones registradas.']);
        }

        $this->bankAccountService->delete($bankAccount);

        return redirect()->back()->with('success', 'Cuenta bancaria eliminada exitosamente.');
    }
}
