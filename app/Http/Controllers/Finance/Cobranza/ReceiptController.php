<?php

namespace App\Http\Controllers\Finance\Cobranza;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Finanzas\TypeReceipt;
use App\Enum\Sales\StatusSales;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Models\AccountsReceivable;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\Receipt;
use App\Services\Finance\RateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function __construct(
        protected RateService $rateService,
    ) {}

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
        Log::info('store context fields:', $request->all());

        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            Log::warning('Validation failed for payment store request:', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all(),
            ]);
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $activePayments = array_filter($validated['payments'], fn ($p) => $p['amount'] > 0);

            if (empty($activePayments)) {
                return redirect()->back()->withErrors(['error' => 'Debe ingresar al menos un monto a cobrar.']);
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
                $acr = AccountsReceivable::with('saleOrder')->findOrFail($paymentData['acr_id']);

                $paymentAmount = (float) $paymentData['amount'];
                $payCurrency = $validated['payment_currency'];
                $docCurrency = $acr->saleOrder->sls_coin ?? 'USD';

                // Get the document's rate record for differential calculation
                $docDate = $acr->saleOrder->sls_date?->format('Y-m-d')
                    ?? $acr->saleOrder->created_at?->format('Y-m-d');
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
                    'btr_type' => 'inflow',
                    'btr_amount' => $paymentAmount,
                    'btr_reference' => $validated['payment_reference'],
                    'btr_description' => $validated['payment_description'] ?: 'Cobro a cliente',
                    'btr_valorcam' => $valorcam,
                    'btr_diferencial' => $diferencial,
                    'btr_balance' => 0,
                    'btr_method' => $validated['payment_method'],
                    'btr_transaction_date' => $validated['payment_date'],
                    'btr_coin' => $validated['payment_currency'],
                ]);

                $bankTransaction->btr_transactionable()->associate($acr);
                $bankTransaction->save();

                $acr->registerPayment(
                    $amountInDocCurrency,
                    TypeReceipt::RECEIPT->value,
                    $bankTransaction->id,
                    $amountUsd,
                );

                // Store differential on receipt
                $receipt = $acr->payments()->where('rec_bank_transaction_id', $bankTransaction->id)->first();
                if ($receipt) {
                    $receipt->rec_diferencial = $diferencial;
                    $receipt->save();
                }
            }

            DB::commit();

            return redirect()->route('accounts-receivable.index')->with('success', 'Comprobante de cobro generado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Exception caught in ReceiptController@store:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Error al procesar el cobro: '.$e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $payment = Receipt::with([
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
        $payment = Receipt::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        if ($payment->rec_type === TypeReceipt::RETURN_CREDIT->value) {
            return redirect()->back()->withErrors(['error' => 'No puede editar un crédito por devolución. Elimínelo y cree uno nuevo.']);
        }

        Log::info("update context fields for payment ID: {$id}:", $request->all());

        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_currency' => 'required|string',
            'payment_reference' => 'required|string|max:50',
            'payment_description' => 'nullable|string|max:150',
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            Log::warning("Validation failed for payment update request (ID: {$id}):", [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->all(),
            ]);
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $acr = $payment->accountsReceivable;

            $oldAmount = $payment->rec_amount;
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
                $bankTx->btr_transaction_date = $validated['payment_date'];
                $bankTx->btr_reference = $validated['payment_reference'];
                $bankTx->btr_description = $validated['payment_description'];
                $bankTx->btr_amount = ($bankTx->btr_amount - $oldAmount) + $newAmount;
                $bankTx->btr_coin = $validated['payment_currency'];
                $bankTx->btr_valorcam = $this->rateService->resolveRateForCoin(
                    $validated['payment_date'],
                    $validated['payment_currency'],
                );
                $bankTx->save();
            }

            $payment->rec_amount = $newAmount;
            $payment->save();

            DB::commit();

            return redirect()->route('accounts-receivable.index')->with('success', 'Cobro actualizado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Exception caught in ReceiptController@update for ID {$id}:", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors(['error' => 'Error al actualizar el cobro: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $payment = Receipt::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        $amount = $payment->rec_amount;

        DB::beginTransaction();
        try {
            $acr = $payment->accountsReceivable;
            $acr->acr_balance += $amount;

            // Restore USD balance
            if ($acr->acr_balance_usd !== null && $payment->bankTransaction) {
                $payCoin = $payment->bankTransaction->btr_coin?->value ?? $payment->bankTransaction->btr_coin;
                if ($payCoin && strtoupper((string) $payCoin) !== 'USD' && $payment->bankTransaction->btr_valorcam > 0) {
                    $paymentUsd = $amount / (float) $payment->bankTransaction->btr_valorcam;
                } else {
                    $paymentUsd = $amount;
                }
                $acr->acr_balance_usd += $paymentUsd;
            }

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
                    $bankTx->btr_diferencial = 0;
                    $bankTx->save();
                }
            }

            $payment->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Cobro eliminado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => 'Error al eliminar el cobro: '.$e->getMessage()]);
        }
    }
}
