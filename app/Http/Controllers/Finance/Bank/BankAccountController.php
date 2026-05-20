<?php

namespace App\Http\Controllers\Finance\Bank;

use App\Enum\Tesoreria\TypeAccount;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index()
    {
        return Inertia::render('Finance/Bank/Index', [
            'bankAccounts' => BankAccount::orderBy('bank_name')->get(),
            'types' => TypeAccount::values(),
            'currencies' => TypeCoin::values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:150|unique:bank_accounts,bank_name',
            'bank_account_number' => 'required|string|max:20|unique:bank_accounts,bank_account_number',
            'bank_type' => 'required|in:'.implode(',', TypeAccount::values()),
            'bank_currency' => 'required|in:'.implode(',', TypeCoin::values()),
            'bank_description' => 'nullable|string|max:150',
            'bank_status' => 'required|boolean',
        ]);

        BankAccount::create($validated);

        return redirect()->back()->with('success', 'Cuenta bancaria creada exitosamente.');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:150|unique:bank_accounts,bank_name,'.$bankAccount->id,
            'bank_account_number' => 'required|string|max:20|unique:bank_accounts,bank_account_number,'.$bankAccount->id,
            'bank_type' => 'required|in:'.implode(',', TypeAccount::values()),
            'bank_currency' => 'required|in:'.implode(',', TypeCoin::values()),
            'bank_description' => 'nullable|string|max:150',
            'bank_status' => 'required|boolean',
        ]);

        $bankAccount->update($validated);

        return redirect()->back()->with('success', 'Cuenta bancaria actualizada exitosamente.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Maybe check if it has transactions before deleting
        if ($bankAccount->bankTransactions()->exists()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar una cuenta con transacciones registradas.']);
        }

        $bankAccount->delete();

        return redirect()->back()->with('success', 'Cuenta bancaria eliminada exitosamente.');
    }
}
