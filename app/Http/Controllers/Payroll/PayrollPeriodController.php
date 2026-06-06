<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Payroll\PayrollPeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollPeriodRequest;
use App\Http\Requests\Payroll\UpdatePayrollPeriodRequest;
use App\Models\PayrollPeriod;
use Inertia\Inertia;

class PayrollPeriodController extends Controller
{
    public function index()
    {
        $periods = PayrollPeriod::withCount('payrolls')
            ->latest()
            ->paginate(10);

        return Inertia::render('Payroll/Periods/Index', [
            'periods' => $periods,
            'statusOptions' => PayrollPeriodStatus::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Payroll/Periods/Create', [
            'statusOptions' => PayrollPeriodStatus::values(),
        ]);
    }

    public function store(StorePayrollPeriodRequest $request)
    {
        PayrollPeriod::create($request->validated());

        return redirect()->route('payroll-periods.index')->with('success', 'Período creado exitosamente.');
    }

    public function show(PayrollPeriod $payrollPeriod)
    {
        return redirect()->route('payroll-periods.edit', $payrollPeriod->id);
    }

    public function edit(PayrollPeriod $payrollPeriod)
    {
        return Inertia::render('Payroll/Periods/Edit', [
            'period' => $payrollPeriod,
            'statusOptions' => PayrollPeriodStatus::values(),
        ]);
    }

    public function update(UpdatePayrollPeriodRequest $request, PayrollPeriod $payrollPeriod)
    {
        $payrollPeriod->update($request->validated());

        return redirect()->route('payroll-periods.index')->with('success', 'Período actualizado exitosamente.');
    }

    public function destroy(PayrollPeriod $payrollPeriod)
    {
        if ($payrollPeriod->payrolls()->exists()) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar un período que tiene nóminas asociadas.']);
        }

        $payrollPeriod->delete();

        return redirect()->route('payroll-periods.index')->with('success', 'Período eliminado exitosamente.');
    }

    public function getPendingPeriods()
    {
        return PayrollPeriod::pending()->get(['id', 'pp_start_date', 'pp_end_date', 'pp_payment_date']);
    }
}
