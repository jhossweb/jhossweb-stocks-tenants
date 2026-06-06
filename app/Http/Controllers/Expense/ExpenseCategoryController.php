<?php

namespace App\Http\Controllers\Expense;

use App\Enum\Expense\ExpenseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseCategoryRequest;
use App\Http\Requests\Expense\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::withCount('expenses')->latest()->paginate(10);

        return Inertia::render('Expense/Categories/Index', [
            'categories' => $categories,
            'typeOptions' => ExpenseType::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Expense/Categories/Create', [
            'typeOptions' => ExpenseType::values(),
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        ExpenseCategory::create($request->validated());

        return redirect()->route('expense-categories.index')->with('success', 'Categoría creada exitosamente.');
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        return redirect()->route('expense-categories.edit', $expenseCategory->id);
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        return Inertia::render('Expense/Categories/Edit', [
            'category' => $expenseCategory,
            'typeOptions' => ExpenseType::values(),
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update($request->validated());

        return redirect()->route('expense-categories.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
