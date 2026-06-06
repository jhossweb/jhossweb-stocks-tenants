<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Payroll\DamageStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StoreEmployeeDamageRequest;
use App\Http\Requests\Payroll\UpdateEmployeeDamageRequest;
use App\Models\Employee;
use App\Models\EmployeeDamage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeDamageController extends Controller
{
    public function index()
    {
        $damages = EmployeeDamage::with('employee')
            ->latest()
            ->paginate(10);

        return Inertia::render('Payroll/Damages/Index', [
            'damages' => $damages,
            'statusOptions' => DamageStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Payroll/Damages/Create', [
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_document']),
            'statusOptions' => DamageStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function store(StoreEmployeeDamageRequest $request)
    {
        EmployeeDamage::create($request->validated());

        return redirect()->route('employee-damages.index')->with('success', 'Daño registrado exitosamente.');
    }

    public function show(EmployeeDamage $employeeDamage)
    {
        return redirect()->route('employee-damages.edit', $employeeDamage->id);
    }

    public function edit(EmployeeDamage $employeeDamage)
    {
        return Inertia::render('Payroll/Damages/Edit', [
            'damage' => $employeeDamage->load('employee'),
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_document']),
            'statusOptions' => DamageStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function update(UpdateEmployeeDamageRequest $request, EmployeeDamage $employeeDamage)
    {
        $employeeDamage->update($request->validated());

        return redirect()->route('employee-damages.index')->with('success', 'Daño actualizado exitosamente.');
    }

    public function destroy(EmployeeDamage $employeeDamage)
    {
        $employeeDamage->delete();

        return redirect()->route('employee-damages.index')->with('success', 'Daño eliminado exitosamente.');
    }

    public function getActiveDamages(Employee $employee)
    {
        return EmployeeDamage::with('employee')
            ->where('eda_employee_id', $employee->id)
            ->active()
            ->get(['id', 'eda_employee_id', 'eda_amount', 'eda_remaining_balance', 'eda_coin', 'eda_description']);
    }
}
