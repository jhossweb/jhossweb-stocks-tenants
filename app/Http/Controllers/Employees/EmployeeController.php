<?php

namespace App\Http\Controllers\Employees;

use App\Enum\Employees\EmployeeStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Employee;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::latest()->paginate(10);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'statusOptions' => EmployeeStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Employees/Create', [
            'statusOptions' => EmployeeStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        Employee::create($request->validated());

        return redirect()->route('employees.index')->with('success', 'Empleado creado exitosamente.');
    }

    public function show(Employee $employee)
    {
        return redirect()->route('employees.edit', $employee->id);
    }

    public function edit(Employee $employee)
    {
        return Inertia::render('Employees/Edit', [
            'employee' => $employee,
            'statusOptions' => EmployeeStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')->with('success', 'Empleado actualizado exitosamente.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Empleado eliminado exitosamente.');
    }

    public function getActiveEmployees()
    {
        return Employee::active()->get(['id', 'emp_name', 'emp_document', 'emp_base_salary', 'emp_salary_coin']);
    }
}
