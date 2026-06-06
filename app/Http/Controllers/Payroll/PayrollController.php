<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Http\Requests\Payroll\UpdatePayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function index()
    {
        $payrolls = Payroll::with(['employee', 'period', 'deductions'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Payroll/Payrolls/Index', [
            'payrolls' => $payrolls,
            'statusOptions' => PayrollStatus::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Payroll/Payrolls/Create', [
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_base_salary', 'emp_salary_coin']),
            'periods' => PayrollPeriod::pending()->get(['id', 'pp_start_date', 'pp_end_date']),
            'statusOptions' => PayrollStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function store(StorePayrollRequest $request)
    {
        Payroll::create($request->validated());

        return redirect()->route('payrolls.index')->with('success', 'Nómina creada exitosamente.');
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'period', 'deductions.deductible']);

        return Inertia::render('Payroll/Payrolls/Show', [
            'payroll' => $payroll,
        ]);
    }

    public function edit(Payroll $payroll)
    {
        if ($payroll->isPaid()) {
            return redirect()->route('payrolls.index')->withErrors(['error' => 'No se puede editar una nómina pagada.']);
        }

        return Inertia::render('Payroll/Payrolls/Edit', [
            'payroll' => $payroll->load('employee', 'period', 'deductions.deductible'),
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_base_salary', 'emp_salary_coin']),
            'statusOptions' => PayrollStatus::values(),
            'coinOptions' => TypeCoin::values(),
        ]);
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll)
    {
        if ($payroll->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se puede editar una nómina pagada.']);
        }

        $payroll->update($request->validated());

        return redirect()->route('payrolls.index')->with('success', 'Nómina actualizada exitosamente.');
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar una nómina pagada.']);
        }

        $payroll->delete();

        return redirect()->route('payrolls.index')->with('success', 'Nómina eliminada exitosamente.');
    }

    public function generate(PayrollPeriod $payrollPeriod)
    {
        $employees = Employee::active()->get();
        $count = 0;

        foreach ($employees as $employee) {
            $exists = Payroll::where('pay_employee_id', $employee->id)
                ->where('pay_period_id', $payrollPeriod->id)
                ->exists();

            if (!$exists) {
                Payroll::create([
                    'pay_employee_id' => $employee->id,
                    'pay_period_id' => $payrollPeriod->id,
                    'pay_base_salary' => $employee->emp_base_salary,
                    'pay_coin' => $employee->emp_salary_coin,
                    'pay_valorcam' => 1,
                    'pay_total_deductions' => 0,
                    'pay_net_salary' => $employee->emp_base_salary,
                    'pay_status' => PayrollStatus::PENDING,
                ]);
                $count++;
            }
        }

        return redirect()->route('payrolls.index')
            ->with('success', "Se generaron {$count} nóminas para el período seleccionado.");
    }

    public function markAsPaid(Payroll $payroll)
    {
        $payroll->markAsPaid();

        return redirect()->back()->with('success', 'Nómina marcada como pagada.');
    }
}
