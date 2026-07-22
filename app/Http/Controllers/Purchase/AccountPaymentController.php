<?php

namespace App\Http\Controllers\Purchase;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Finanzas\TypePayment;
use App\Enum\Purchases\StatusPurchases;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Models\AccountPayments;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Rate;
use App\Models\Supplier;
use App\Services\Finance\RateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountPaymentController extends Controller
{
    public function __construct(
        protected RateService $rateService,
    ) {}

    /**
     * Show the form for creating a new payment receipt.
     */
    public function create()
    {
        return Inertia::render('Finance/Payments/Create', [
            'suppliers' => Supplier::orderBy('supplier_name')->get(['id', 'supplier_name']),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    /**
     * Get pending invoices for a specific supplier.
     */
    public function getPendingInvoices(Supplier $supplier)
    {
        $pending = AccountsPayable::with('purchaseOrder')
            ->whereHas('purchaseOrder', function ($query) use ($supplier) {
                $query->where('invoice_supplier_id', $supplier->id);
            })
            ->where('acp_balance', '>', 0)
            ->get();

        return response()->json($pending);
    }

    /**
     * Store a newly created payment receipt in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_currency' => 'required|string',
            'payment_rate' => 'required|numeric',
            'payment_method' => 'required|string',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'payments' => 'required|array',
            'payments.*.acp_id' => 'required|exists:accounts_payables,id',
            'payments.*.amount' => 'required|numeric|min:0',
        ]);

        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $totalPayment = (float) array_sum(array_column($validated['payments'], 'amount'));

        if ($bankAccount->bank_balance < $totalPayment) {
            return redirect()->back()->withErrors([
                'bank_account_id' => 'Saldo insuficiente. Saldo disponible en '.$bankAccount->bank_name.': '.number_format((float) $bankAccount->bank_balance, 2).' '.$bankAccount->bank_currency,
            ]);
        }

        DB::beginTransaction();
        try {
            $activePayments = array_filter($validated['payments'], fn ($p) => $p['amount'] > 0);

            if (empty($activePayments)) {
                return redirect()->back()->withErrors(['error' => 'Debe ingresar al menos un monto a pagar.']);
            }

            $payRate = $this->rateService->getExactRateForDate($validated['payment_date']);

            if (! $payRate) {
                return redirect()->back()->withErrors(['payment_date' => 'No existe tasa de cambio para la fecha seleccionada.']);
            }

            $valorcam = $this->rateService->resolveRateForCoin(
                $validated['payment_date'],
                $validated['payment_currency'],
            );

            foreach ($activePayments as $paymentData) {
                $acp = AccountsPayable::with('purchaseOrder')->findOrFail($paymentData['acp_id']);

                $paymentAmount = (float) $paymentData['amount'];
                $payCurrency = $validated['payment_currency'];
                $docCurrency = $acp->purchaseOrder->invoice_coin ?? 'USD';

                // Get the document's rate record for differential calculation
                $docDate = $acp->purchaseOrder->invoice_date?->format('Y-m-d')
                    ?? $acp->purchaseOrder->created_at?->format('Y-m-d');
                $docRate = $docDate
                    ? ($this->rateService->getExactRateForDate($docDate) ?? $payRate)
                    : $payRate;

                // Convert payment to document currency (existing behavior)
                $rate = $this->rateService->getExactRateForDate($validated['payment_date']);
                $amountInDocCurrency = Rate::convert($paymentAmount, $payCurrency, $docCurrency, $rate);

                // Calculate differential in USD
                $diferencial = $this->rateService->calculateDiferencial(
                    $paymentAmount,
                    $payCurrency,
                    $payRate,
                    $docRate,
                );

                // Convert payment to USD for balance tracking
                $amountUsd = Rate::convert($paymentAmount, $payCurrency, 'USD', $payRate);

                $bankTransaction = BankTransaction::create([
                    'btr_bank_account_id' => $validated['bank_account_id'],
                    'btr_type' => 'outflow',
                    'btr_amount' => $paymentAmount,
                    'btr_reference' => $validated['payment_reference'],
                    'btr_description' => $validated['payment_description'] ?: 'Pago a proveedor',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $validated['payment_method'],
                    'btr_transaction_date' => $validated['payment_date'],
                    'btr_coin' => $validated['payment_currency'],
                ]);

                $bankTransaction->btr_transactionable()->associate($acp);
                $bankTransaction->save();

                $acp->registerPayment(
                    $amountInDocCurrency,
                    TypePayment::PAYMENT->value,
                    $bankTransaction->id,
                    null,
                    $amountUsd,
                );

                // Store differential on account payment
                $accountPayment = $acp->payments()->where('apy_bank_transaction_id', $bankTransaction->id)->first();
                if ($accountPayment) {
                    $accountPayment->apy_diferencial = $diferencial;
                    $accountPayment->save();
                }
            }

            DB::commit();

            return redirect()->route('accounts-payable.index')->with('success', 'Comprobante de pago generado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al procesar el pago: '.$e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $payment = AccountPayments::with(['accountPayable.purchaseOrder.supplier', 'bankTransaction.bankAccount'])->findOrFail($id);

        return Inertia::render('Finance/Payments/Create', [
            'payment' => $payment,
            'suppliers' => Supplier::orderBy('supplier_name')->get(['id', 'supplier_name']),
            'bankAccounts' => BankAccount::where('bank_status', true)->get(),
            'latestRate' => Rate::orderBy('rate_date', 'desc')->first(),
            'rates' => Rate::orderBy('rate_date', 'desc')->get(),
            'currencies' => TypeCoin::values(),
            'methods' => TransactionMethod::values(),
        ]);
    }

    /**
     * Update the specified payment in storage.
     */
    public function update(Request $request, $id)
    {
        $payment = AccountPayments::with(['accountPayable.purchaseOrder', 'bankTransaction'])->findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_currency' => 'required|string',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
        $oldBankTx = $payment->bankTransaction;
        $effectiveBalance = (float) $bankAccount->bank_balance;
        $newAmount = (float) $validated['amount'];

        if ($oldBankTx && (int) $oldBankTx->btr_bank_account_id === (int) $validated['bank_account_id']) {
            $effectiveBalance += (float) $oldBankTx->btr_amount;
        }

        if ($effectiveBalance < $newAmount) {
            return redirect()->back()->withErrors([
                'amount' => 'Saldo insuficiente en '.$bankAccount->bank_name.'. Saldo efectivo disponible: '.number_format($effectiveBalance, 2).' '.$bankAccount->bank_currency,
            ]);
        }

        DB::beginTransaction();
        try {
            $acp = $payment->accountPayable;

            // Revert previous amount first
            $oldAmount = $payment->apy_amount;
            $acp->acp_balance += $oldAmount;

            // Check if new amount exceeds the available balance of the invoice
            $newAmount = $validated['amount'];
            if ($newAmount > $acp->acp_balance) {
                throw new \Exception('El monto no puede ser mayor a la deuda pendiente original de la factura ($'.number_format($acp->acp_balance, 2).').');
            }

            $acp->acp_balance -= $newAmount;

            if ($acp->acp_balance <= 0) {
                $acp->acp_status = StatusPurchases::PAID->value;
                $acp->acp_balance = 0;
            } elseif ($acp->acp_balance == $acp->acp_total) {
                $acp->acp_status = StatusPurchases::PENDING->value;
            } else {
                $acp->acp_status = StatusPurchases::PARTIAL->value;
            }
            $acp->save();

            // Sync status with original PurchaseOrder
            if ($acp->purchaseOrder) {
                $acp->purchaseOrder->invoice_status = $acp->acp_status;
                $acp->purchaseOrder->save();
            }

            // Update Bank Transaction
            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_bank_account_id = $validated['bank_account_id'];
                $bankTx->btr_transaction_date = $validated['payment_date'];
                $bankTx->btr_reference = $validated['payment_reference'];
                $bankTx->btr_description = $validated['payment_description'];

                // Adjust tx total
                $bankTx->btr_coin = $validated['payment_currency'];
                $bankTx->btr_amount = ($bankTx->btr_amount - $oldAmount) + $newAmount;
                $bankTx->save();
            }

            $payment->apy_amount = $newAmount;
            $payment->save();

            DB::commit();

            return redirect()->route('accounts-payable.index')->with('success', 'Pago actualizado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al actualizar el pago: '.$e->getMessage()]);
        }
    }

    /**
     * Remove the specified payment from storage.
     */
    public function destroy($id)
    {
        $payment = AccountPayments::with(['accountPayable.purchaseOrder', 'bankTransaction'])->findOrFail($id);
        $amount = $payment->apy_amount;

        DB::beginTransaction();
        try {
            // Restore Account Payable
            $acp = $payment->accountPayable;
            $acp->acp_balance += $amount;

            // Restore USD balance
            if ($acp->acp_balance_usd !== null && $payment->bankTransaction) {
                $payCoin = $payment->bankTransaction->btr_coin?->value ?? $payment->bankTransaction->btr_coin;
                if ($payCoin && strtoupper((string) $payCoin) !== 'USD' && $payment->bankTransaction->btr_valorcam > 0) {
                    $paymentUsd = $amount / (float) $payment->bankTransaction->btr_valorcam;
                } else {
                    $paymentUsd = $amount;
                }
                $acp->acp_balance_usd += $paymentUsd;
            }

            if ($acp->acp_balance == $acp->acp_total) {
                $acp->acp_status = StatusPurchases::PENDING->value;
            } elseif ($acp->acp_balance > 0) {
                $acp->acp_status = StatusPurchases::PARTIAL->value;
            }
            $acp->save();

            // Sync status with PurchaseOrder
            if ($acp->purchaseOrder) {
                $acp->purchaseOrder->invoice_status = $acp->acp_status;
                $acp->purchaseOrder->save();
            }

            // Adjust Bank Transaction
            $bankTx = $payment->bankTransaction;
            if ($bankTx) {
                $bankTx->btr_amount -= $amount;
                if ($bankTx->btr_amount <= 0) {
                    $bankTx->delete();
                } else {
                    $bankTx->btr_diferencial = 0;
                    $bankTx->save();
                }
            }

            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Pago eliminado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al eliminar el pago: '.$e->getMessage()]);
        }
    }
}
