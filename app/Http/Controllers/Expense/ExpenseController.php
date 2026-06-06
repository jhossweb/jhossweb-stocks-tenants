<?php

namespace App\Http\Controllers\Expense;

use App\Enum\Expense\ExpenseStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
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
        ]);
    }

    public function create()
    {
        return Inertia::render('Expense/Expenses/Create', [
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function store(StoreExpenseRequest $request)
    {
        Expense::create($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Gasto creado exitosamente.');
    }

    public function show(Expense $expense)
    {
        return redirect()->route('expenses.edit', $expense->id);
    }

    public function edit(Expense $expense)
    {
        return Inertia::render('Expense/Expenses/Edit', [
            'expense' => $expense->load('category'),
            'categories' => ExpenseCategory::all(['id', 'exc_name', 'exc_type']),
            'statusOptions' => ExpenseStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Gasto actualizado exitosamente.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Gasto eliminado exitosamente.');
    }
}
