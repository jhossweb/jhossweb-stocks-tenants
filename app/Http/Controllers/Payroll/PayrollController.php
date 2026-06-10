<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Payroll\PayrollStatus;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\PayPayrollRequest;
use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Http\Requests\Payroll\UpdatePayrollRequest;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\Rate;
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
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Payroll/Payrolls/Create', [
            'employees' => Employee::active()->get(['id', 'emp_name', 'emp_base_salary', 'emp_salary_coin']),
            'periods' => PayrollPeriod::pending()->get(['id', 'pp_start_date', 'pp_end_date']),
            'statusOptions' => PayrollStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
        ]);
    }

    public function store(StorePayrollRequest $request)
    {
        Payroll::create($request->validated());

        return redirect()->route('payrolls.index')->with('success', 'Nómina creada exitosamente.');
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['employee', 'period', 'deductions.deductible', 'payments.bankAccount']);

        return Inertia::render('Payroll/Payrolls/Show', [
            'payroll' => $payroll,
            'bankAccounts' => BankAccount::active()->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
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
            'periods' => PayrollPeriod::pending()->get(['id', 'pp_start_date', 'pp_end_date']),
            'statusOptions' => PayrollStatus::values(),
            'coinOptions' => TypeCoin::values(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
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
        $latestRate = Rate::orderBy('rate_date', 'desc')->first();
        $count = 0;

        foreach ($employees as $employee) {
            $exists = Payroll::where('pay_employee_id', $employee->id)
                ->where('pay_period_id', $payrollPeriod->id)
                ->exists();

            if (!$exists) {
                $pay_valorcam = match ($employee->emp_salary_coin) {
                    TypeCoin::USD => 1,
                    TypeCoin::COP => $latestRate?->rate_cop ?? 1,
                    TypeCoin::EUR => $latestRate?->rate_eur ?? 1,
                    default => $latestRate?->rate_usd ?? 1,
                };

                Payroll::create([
                    'pay_employee_id' => $employee->id,
                    'pay_period_id' => $payrollPeriod->id,
                    'pay_base_salary' => $employee->emp_base_salary,
                    'pay_coin' => $employee->emp_salary_coin,
                    'pay_valorcam' => $pay_valorcam,
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

    public function markAsPaid(PayPayrollRequest $request, Payroll $payroll)
    {
        if ($payroll->isPaid()) {
            return redirect()->back()->withErrors(['error' => 'La nómina ya está pagada.']);
        }

        $payroll->registerPayment(
            amountInBankCurrency: (float) $request->amount,
            bankAccountId: (int) $request->bank_account_id,
            reference: $request->reference,
            description: $request->description,
            paymentCurrency: $request->payment_currency,
            paymentRate: (float) $request->payment_rate,
        );

        return redirect()->back()->with('success', 'Nómina pagada exitosamente.');
    }
}
