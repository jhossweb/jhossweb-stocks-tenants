<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Payroll\LoanStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreEmployeeLoanRequest;
use App\Http\Requests\Payroll\UpdateEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeLoanController extends Controller
{
    public function index()
    {
        $loans = EmployeeLoan::with('employee')
            ->latest()
            ->paginate(10);

        return Inertia::render('Payroll/Loans/Index', [
            'loans' => $loans,
            'statusOptions' => LoanStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Payroll/Loans/Create', [
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_document']),
            'statusOptions' => LoanStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function store(StoreEmployeeLoanRequest $request)
    {
        EmployeeLoan::create($request->validated());

        return redirect()->route('employee-loans.index')->with('success', 'Préstamo creado exitosamente.');
    }

    public function show(EmployeeLoan $employeeLoan)
    {
        return redirect()->route('employee-loans.edit', $employeeLoan->id);
    }

    public function edit(EmployeeLoan $employeeLoan)
    {
        return Inertia::render('Payroll/Loans/Edit', [
            'loan' => $employeeLoan->load('employee'),
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_document']),
            'statusOptions' => LoanStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function update(UpdateEmployeeLoanRequest $request, EmployeeLoan $employeeLoan)
    {
        $employeeLoan->update($request->validated());

        return redirect()->route('employee-loans.index')->with('success', 'Préstamo actualizado exitosamente.');
    }

    public function destroy(EmployeeLoan $employeeLoan)
    {
        $employeeLoan->delete();

        return redirect()->route('employee-loans.index')->with('success', 'Préstamo eliminado exitosamente.');
    }

    public function getActiveLoans(Employee $employee)
    {
        return EmployeeLoan::with('employee')
            ->where('elo_employee_id', $employee->id)
            ->active()
            ->get(['id', 'elo_employee_id', 'elo_amount', 'elo_remaining_balance', 'elo_coin', 'elo_description']);
    }
}
