<?php

namespace App\Http\Controllers\Payroll;

use App\DTOs\Payroll\PayrollPaymentDTO;
use App\Enum\Finanzas\TransactionMethod;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollPaymentRequest;
use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Rate;
use App\Services\Payroll\PayrollPaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PayrollPaymentController extends Controller
{
    public function __construct(
        private readonly PayrollPaymentService $payrollPaymentService,
    ) {}

    public function create(Request $request)
    {
        return Inertia::render('Payroll/PayrollPayments/Create', [
            'employees' => Employee::orderBy('emp_name')->get(['id', 'emp_name', 'emp_document']),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
            'employeeId' => $request->query('employee_id'),
        ]);
    }

    public function getPendingPayrolls(Employee $employee)
    {
        $pending = Payroll::with('employee', 'period')
            ->where('pay_employee_id', $employee->id)
            ->where('pay_status', 'pendiente')
            ->get();

        return response()->json($pending);
    }

    public function store(StorePayrollPaymentRequest $request)
    {
        $result = $this->payrollPaymentService->pay(
            PayrollPaymentDTO::fromArray($request->validated()),
        );

        if (! $result['success']) {
            return redirect()->back()->withErrors(['error' => $result['message']]);
        }

        return redirect()->route('payrolls.index')->with('success', 'Pago de nómina registrado exitosamente.');
    }
}
