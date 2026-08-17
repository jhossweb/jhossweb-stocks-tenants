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
use App\Services\Expense\ExpenseService;
use App\Services\Finance\RateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService,
        protected RateService $rateService,
    ) {}

    public function index()
    {
        $query = Expense::with('category', 'payments.bankAccount')->latest();

        if (request('category')) {
            $query->where('exp_category_id', request('category'));
        }

        if (request('status')) {
            $query->where('exp_status', request('status'));
        }

        $expenses = $query->paginate(10)->withQueryString();

        return Inertia::render('Expense/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => $this->rateService->getLatestRate(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(['rate_date', 'rate_usd', 'rate_cop', 'rate_eur', 'rate_cop_bs']),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Expense/Expenses/Create', [
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => $this->rateService->getLatestRate(),
            'rates' => Rate::all()->map(fn ($rate) => [
                'rate_date' => optional($rate->rate_date)->format('Y-m-d'),
                'rate_usd' => $rate->rate_usd,
                'rate_cop' => $rate->rate_cop,
                'rate_eur' => $rate->rate_eur,
                'rate_cop_bs' => $rate->rate_cop_bs,
            ])->values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->expenseService->create($request->validated());

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
            'coinOptions' => TypeCoin::values(),
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => $this->rateService->getLatestRate(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se puede editar un gasto pagado.']);
        }

        $this->expenseService->update($expense, $request->validated());

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado exitosamente.');
    }

    public function destroy(Expense $expense)
    {
        try {
            $this->expenseService->delete($expense);

            return redirect()->route('expenses.index')->with('success', 'Gasto eliminado exitosamente.');
        } catch (\LogicException $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function pay(PayExpenseRequest $request, Expense $expense)
    {
        if ($expense->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'El gasto ya está pagado.']);
        }

        $this->expenseService->registerPayment(
            expense: $expense,
            amountInBankCurrency: (float) $request->amount,
            bankAccountId: (int) $request->bank_account_id,
            reference: $request->reference,
            description: $request->description,
            paymentCurrency: $request->payment_currency,
            paymentRate: (float) $request->payment_rate,
            paymentMethod: $request->payment_method,
            paymentDate: $request->payment_date,
        );

        $expense->refresh();

        $message = $expense->isPaid()
            ? 'Gasto pagado exitosamente.'
            : 'Pago registrado exitosamente.';

        return redirect()->back()->with('success', $message);
    }

    public function cancelPayment(Request $request, Expense $expense)
    {
        $request->validate([
            'bank_transaction_id' => 'required|exists:bank_transactions,id',
        ]);

        $this->expenseService->cancelPayment($expense, (int) $request->bank_transaction_id);

        return redirect()->back()->with('success', 'Pago cancelado exitosamente. El saldo bancario ha sido revertido.');
    }
}
