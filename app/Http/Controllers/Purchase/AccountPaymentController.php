<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\AccountsPayable;
use App\Models\BankAccount;
use App\Models\Rate;
use App\Models\Supplier;
use App\Enum\TypeCoin;
use App\Enum\Tesoreria\TransactionMethod;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountPaymentController extends Controller
{
    /**
     * Show the form for creating a new payment receipt.
     */
    public function create()
    {
        return Inertia::render('Finance/Payments/Create', [
            'suppliers' => Supplier::orderBy('supplier_name')->get(['id', 'supplier_name', 'supplier_code']),
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

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Filter out items with 0 amount
            $activePayments = array_filter($validated['payments'], fn($p) => $p['amount'] > 0);
            
            if (empty($activePayments)) {
                return redirect()->back()->withErrors(['error' => 'Debe ingresar al menos un monto a pagar.']);
            }

            $totalAmount = collect($activePayments)->sum('amount');
            
            // Create Bank Transaction
            $bankTransaction = \App\Models\BankTransaction::create([
                'btr_bank_account_id' => $validated['bank_account_id'],
                'btr_type' => 'outflow',
                'btr_amount' => $totalAmount,
                'btr_reference' => $validated['payment_reference'],
                'btr_description' => $validated['payment_description'] ?: ('Pago a proveedor - ' . $validated['payment_method']),
                'btr_valorcam' => $validated['payment_rate'],
                'btr_balance' => 0, // Handled by observer
            ]);

            // Note: morph for transactionables could be used here if we had a Receipt model
            // $bankTransaction->btr_transactionable()->associate(...)

            foreach ($activePayments as $paymentData) {
                $acp = AccountsPayable::with('purchaseOrder')->findOrFail($paymentData['acp_id']);
                
                $amountToRegister = $paymentData['amount'];
                $docCurrency = $acp->purchaseOrder->invoice_coin ?? 'USD';
                $payCurrency = $validated['payment_currency'];
                $rate = (float) $validated['payment_rate'];

                if ($docCurrency !== $payCurrency) {
                    // Doc COP, Pay USD -> DocValue = PayValue * Rate
                    if ($docCurrency === 'COP' && $payCurrency === 'USD') {
                        $amountToRegister = $amountToRegister * $rate;
                    } 
                    // Doc USD, Pay COP/BS/VES -> DocValue = PayValue / Rate
                    elseif ($docCurrency === 'USD' && (in_array($payCurrency, ['COP', 'BS', 'VES']))) {
                        $amountToRegister = $amountToRegister / $rate;
                    }
                }

                $acp->registerPayment(
                    $amountToRegister,
                    \App\Enum\Tesoreria\TypePayment::PAYMENT->value,
                    $bankTransaction->id
                );
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('accounts-payable.index')->with('success', 'Comprobante de pago generado exitosamente.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al procesar el pago: ' . $e->getMessage()]);
        }
    }
}
