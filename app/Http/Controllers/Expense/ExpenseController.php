<?php

namespace App\Http\Controllers\Expense;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\Finanzas\TransactionMethod;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\PayExpenseRequest;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Rate;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index()
    {
        $expenses = Expense::with('category')
            ->latest()
            ->paginate(10);

        return Inertia::render('Expense/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Expense/Expenses/Create', [
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $data = $request->validated();
        $data['exp_paid_amount'] = 0;
        $data['exp_remaining_balance'] = $data['exp_amount'];

        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'Gasto creado exitosamente.');
    }

    public function show(Expense $expense)
    {
        return redirect()->route('expenses.edit', $expense->id);
    }

    public function edit(Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->route('expenses.index')->withErrors(['error' => 'No se puede editar un gasto pagado.']);
        }

        return Inertia::render('Expense/Expenses/Edit', [
            'expense' => $expense->load('category', 'payments.bankAccount'),
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se puede editar un gasto pagado.']);
        }

        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado exitosamente.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar un gasto pagado.']);
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado exitosamente.');
    }

    public function pay(PayExpenseRequest $request, Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'El gasto ya está pagado.']);
        }

        $expense->registerPayment(
            amountInBankCurrency: (float) $request->amount,
            bankAccountId: (int) $request->bank_account_id,
            reference: $request->reference,
            description: $request->description,
            paymentCurrency: $request->payment_currency,
            paymentRate: (float) $request->payment_rate,
        );

        $message = $expense->isPaid()
            ? 'Gasto pagado exitosamente.'
            : 'Pago registrado exitosamente.';

        return redirect()->back()->with('success', $message);
    }
}
