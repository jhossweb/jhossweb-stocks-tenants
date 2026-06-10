<?php

namespace App\Http\Controllers\Payroll;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Finanzas\TypePayment;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayrollPaymentController extends Controller
{
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'employee_id' => 'required|exists:employees,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_currency' => 'required|string',
            'payment_rate' => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'payments' => 'required|array',
            'payments.*.pay_id' => 'required|exists:payrolls,id',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $activePayments = array_filter($validated['payments'], fn ($p) => $p['amount'] > 0);

            if (empty($activePayments)) {
                return redirect()->back()->withErrors(['error' => 'Debe ingresar al menos un monto a pagar.']);
            }

            foreach ($activePayments as $paymentData) {
                $payroll = Payroll::with('employee')->findOrFail($paymentData['pay_id']);

                $payroll->registerPayment(
                    $paymentData['amount'],
                    $validated['bank_account_id'],
                    $validated['payment_reference'],
                    $validated['payment_description'] ?: ('Pago de nómina - '.$validated['payment_method']),
                );

                $bankTransaction = $payroll->payments()->latest()->first();
                if ($bankTransaction) {
                    $bankTransaction->update([
                        'btr_type' => 'outflow',
                        'btr_valorcam' => $validated['payment_rate'],
                        'btr_coin' => $validated['payment_currency'],
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('payrolls.index')->with('success', 'Pago de nómina registrado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al procesar el pago: '.$e->getMessage()]);
        }
    }
}
