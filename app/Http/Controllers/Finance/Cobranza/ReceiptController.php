<?php

namespace App\Http\Controllers\Finance\Cobranza;

use App\Enum\Finanzas\TransactionMethod;
use App\Enum\Finanzas\TypeReceipt;
use App\Enum\TypeCoin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Cobranza\StoreReceiptRequest;
use App\Http\Requests\Finance\Cobranza\UpdateReceiptRequest;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\Receipt;
use App\Repositories\Finance\Cobranza\ReceiptRepository;
use App\Services\Finance\Cobranza\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
        protected ReceiptRepository $receiptRepo,
    ) {}

    public function index(Request $request)
    {
        $receipts = $this->receiptRepo->history(
            search: $request->string('search')->toString() ?: null,
        );

        return Inertia::render('Finance/Receipts/Index', [
            'receipts' => $receipts,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load([
            'accountsReceivable.saleOrder.customer',
            'bankTransaction.bankAccount',
            'accountsReceivable.vueltoTransactions.bankAccount',
        ]);

        $vueltos = $receipt->accountsReceivable?->vueltoTransactions->values() ?? [];

        // Load all movements for this receipt group
        $movements = Receipt::with([
            'bankTransaction.bankAccount',
            'accountsReceivable.saleOrder.customer',
        ])
            ->where('rec_account_receivable_id', $receipt->rec_account_receivable_id)
            ->latest('created_at')
            ->get();

        return Inertia::render('Finance/Receipts/Show', [
            'receipt' => $receipt,
            'vueltos' => $vueltos,
            'movements' => $movements,
        ]);
    }

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
        return response()->json(
            $this->receiptRepo->getPendingReceivables($customer)
        );
    }

    public function store(StoreReceiptRequest $request)
    {
        $validated = $request->validated();

        Log::info('store context fields:', $validated);

        try {
            $this->receiptService->processPayments($validated);

            return redirect()->route('accounts-receivable.index')
                ->with('success', 'Comprobante de cobro generado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Exception caught in ReceiptController@store:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al procesar el cobro: '.$e->getMessage()]);
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

    public function update(UpdateReceiptRequest $request, $id)
    {
        $payment = Receipt::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        if ($payment->rec_type === TypeReceipt::RETURN_CREDIT->value) {
            return redirect()->back()
                ->withErrors(['error' => 'No puede editar un crédito por devolución. Elimínelo y cree uno nuevo.']);
        }

        $validated = $request->validated();

        Log::info("update context fields for payment ID: {$id}:", $validated);

        try {
            $this->receiptService->updatePayment($payment, $validated);

            return redirect()->route('accounts-receivable.index')
                ->with('success', 'Cobro actualizado exitosamente.');
        } catch (\Throwable $e) {
            Log::error("Exception caught in ReceiptController@update for ID {$id}:", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el cobro: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $payment = Receipt::with([
            'accountsReceivable.saleOrder',
            'bankTransaction',
        ])->findOrFail($id);

        try {
            $this->receiptService->deletePayment($payment);

            return redirect()->route('receipts.index')
                ->with('success', 'Cobro eliminado exitosamente.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Error al eliminar el cobro: '.$e->getMessage()]);
        }
    }
}
