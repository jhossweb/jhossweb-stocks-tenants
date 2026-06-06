<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Payroll\DamageStatus;
use App\Enum\Payroll\LoanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollDeductionRequest;
use App\Models\EmployeeDamage;
use App\Models\EmployeeLoan;
use App\Models\Payroll;
use App\Models\PayrollDeduction;

class PayrollDeductionController extends Controller
{
    public function store(StorePayrollDeductionRequest $request, Payroll $payroll)
    {
        if ($payroll->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se pueden agregar descuentos a una nómina ya pagada.']);
        }

        $validated = $request->validated();

        $payroll->registerDeduction(
            $validated['pde_deductible_type'],
            $validated['pde_deductible_id'],
            $validated['pde_percentage']
        );

        return redirect()->back()->with('success', 'Descuento aplicado exitosamente.');
    }

    public function destroy(PayrollDeduction $payrollDeduction)
    {
        $payroll = $payrollDeduction->payroll;

        if ($payroll->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'No se pueden eliminar descuentos de una nómina ya pagada.']);
        }

        $deductible = $payrollDeduction->deductible;
        if ($deductible) {
            if ($deductible instanceof EmployeeLoan) {
                $deductible->elo_remaining_balance += $payrollDeduction->pde_amount;
                if ($deductible->elo_remaining_balance > 0) {
                    $deductible->elo_status = LoanStatus::ACTIVE;
                }
            } elseif ($deductible instanceof EmployeeDamage) {
                $deductible->eda_remaining_balance += $payrollDeduction->pde_amount;
                if ($deductible->eda_remaining_balance > 0) {
                    $deductible->eda_status = DamageStatus::ACTIVE;
                }
            }
            $deductible->save();
        }

        $payrollDeduction->delete();
        $payroll->recalculate();

        return redirect()->back()->with('success', 'Descuento eliminado exitosamente.');
    }
}
