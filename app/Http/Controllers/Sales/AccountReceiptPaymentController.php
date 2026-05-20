<?php

namespace App\Http\Controllers\Sales;

use App\Enum\Sales\StatusSales;
use App\Enum\Tesoreria\TransactionMethod;
use App\Enum\Tesoreria\TypePayment;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Models\AccountReceiptPayments;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountReceiptPaymentController extends Controller
{
    public function create()
    {
        return Inertia::render('Finance/Receipts/Create', [
            'customers' => Customer::orderBy('clt_name')->get(['id', 'clt_name']),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function getPendingReceivables(Customer $customer)
    {
        $pending = AccountsReceivable::with('saleOrder')
            ->whereHas('saleOrder', function ($query) use ($customer) {
                $query->where('sls_customer_id', $customer->id);
            })
            ->where('acr_balance', '>', 0)
            ->get();

        return response()->json($pending);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_currency' => 'required|string',
            'payment_rate' => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'payments' => 'required|array',
            'payments.*.acr_id' => 'required|exists:accounts_receivable,id',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $activePayments = array_filter($validated['payments'], fn ($p) => $p['amount'] > 0);

            if (empty($activePayments)) {
                return redirect()->back()->withErrors(['error' => 'Debe ingresar al menos un monto a cobrar.']);
            }

            foreach ($activePayments as $paymentData) {
                $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);

                $amountToRegister = $paymentData['amount'];
                $docCurrency = $acr->saleOrder->sls_coin ?? 'USD';
                $payCurrency = $validated['payment_currency'];
                $rate = (float) $validated['payment_rate'];

                if ($docCurrency !== $payCurrency) {
                    if ($docCurrency === 'COP' && $payCurrency === 'USD') {
                        $amountToRegister = $amountToRegister * $rate;
                    } elseif ($docCurrency === 'USD' && in_array($payCurrency, ['COP', 'BS', 'VES'])) {
                        $amountToRegister = $amountToRegister / $rate;
                    }
                }

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $validated['bank_account_id'],
                    'btr_type' => 'inflow',
                    'btr_amount' => $amountToRegister,
                    'btr_reference' => $validated['payment_reference'],
                    'btr_description' => $validated['payment_description'] ?: ('Cobro a cliente - '.$validated['payment_method']),
                    'btr_valorcam' => $validated['payment_rate'],
                    'btr_balance' => 0,
                ]);

                $bankTransaction->btr_transactionable()->associate($acr);
                $bankTransaction->save();

                $acr->registerPayment(
                    $amountToRegister,
                    TypePayment::PAYMENT->value,
                    $bankTransaction->id,
                );
            }

            DB::commit();

            return redirect()->route('accounts-receivable.index')->with('success', 'Comprobante de cobro generado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al procesar el cobro: '.$e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $payment = AccountReceiptPayments::with([
            'accountsReceivable.saleOrder.customer',
            'bankTransaction.bankAccount',
        ])->findOrFail($id);

        return Inertia::render('Finance/Receipts/Create', [
            'payment' => $payment,
            'customers' => Customer::orderBy('clt_name')->get(['id', 'clt_name']),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = AccountReceiptPayments::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $acr = $payment->accountsReceivable;

            $oldAmount = $payment->arp_amount;
            $acr->acr_balance += $oldAmount;

            $newAmount = $validated['amount'];
            if ($newAmount > $acr->acr_balance) {
                throw new \Exception('El monto no puede ser mayor a la deuda pendiente de la factura ($'.number_format($acr->acr_balance, 2).').');
            }

            $acr->acr_balance -= $newAmount;

            if ($acr->acr_balance <= 0) {
                $acr->acr_status = StatusSales::PAID->value;
                $acr->acr_balance = 0;
            } elseif ($acr->acr_balance == $acr->acr_total) {
                $acr->acr_status = StatusSales::PENDING->value;
            } else {
                $acr->acr_status = StatusSales::PARTIAL->value;
            }
            $acr->save();

            if ($acr->saleOrder) {
                $acr->saleOrder->sls_status = $acr->acr_status;
                $acr->saleOrder->save();
            }

            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_bank_account_id = $validated['bank_account_id'];
                $bankTx->created_at = $validated['payment_date'];
                $bankTx->btr_reference = $validated['payment_reference'];
                $bankTx->btr_description = $validated['payment_description'];
                $bankTx->btr_amount = ($bankTx->btr_amount - $oldAmount) + $newAmount;
                $bankTx->save();
            }

            $payment->arp_amount = $newAmount;
            $payment->save();

            DB::commit();

            return redirect()->route('accounts-receivable.index')->with('success', 'Cobro actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al actualizar el cobro: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $payment = AccountReceiptPayments::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        $amount = $payment->arp_amount;

        DB::beginTransaction();
        try {
            $acr = $payment->accountsReceivable;
            $acr->acr_balance += $amount;

            if ($acr->acr_balance == $acr->acr_total) {
                $acr->acr_status = StatusSales::PENDING->value;
            } elseif ($acr->acr_balance > 0) {
                $acr->acr_status = StatusSales::PARTIAL->value;
            }
            $acr->save();

            if ($acr->saleOrder) {
                $acr->saleOrder->sls_status = $acr->acr_status;
                $acr->saleOrder->save();
            }

            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_amount -= $amount;
                if ($bankTx->btr_amount <= 0) {
                    $bankTx->delete();
                } else {
                    $bankTx->save();
                }
            }

            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Cobro eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al eliminar el cobro: '.$e->getMessage()]);
        }
    }
}
