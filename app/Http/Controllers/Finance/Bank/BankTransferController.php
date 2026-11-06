<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Bank;

use App\DTOs\Finance\Bank\BankTransferData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\Bank\StoreBankTransferRequest;
use App\Http\Requests\Finance\Bank\StoreBankTransferVoidRequest;
use App\Models\BankAccount;
use App\Models\BankTransfer;
use App\Services\Finance\Bank\BankTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BankTransferController extends Controller
{
    public function __construct(
        private readonly BankTransferService $service,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['bank_account_id', 'date_from', 'date_to', 'search']);

        return Inertia::render('Finance/Transfers/Index', [
            'transfers' => $this->service->paginate($filters),
            'filters' => $filters,
            'bankAccounts' => BankAccount::query()->orderBy('bank_name')->get(['id', 'bank_name', 'bank_currency']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Transfers/Create', [
            'bankAccounts' => BankAccount::query()
                ->active()
                ->orderBy('bank_name')
                ->get(['id', 'bank_name', 'bank_currency', 'bank_balance']),
        ]);
    }

    public function store(StoreBankTransferRequest $request): RedirectResponse
    {
        try {
            $transfer = $this->service->create(
                BankTransferData::fromArray($request->validated()),
                $request->user(),
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('bank-transfers.show', $transfer)
            ->with('success', "Transferencia {$transfer->bt_number} registrada.");
    }

    public function show(BankTransfer $bankTransfer): Response
    {
        return Inertia::render('Finance/Transfers/Show', [
            'transfer' => $this->service->find($bankTransfer),
        ]);
    }

    public function void(StoreBankTransferVoidRequest $request, BankTransfer $bankTransfer): RedirectResponse
    {
        try {
            $this->service->void($bankTransfer, $request->user(), $request->validated('reason'));
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('bank-transfers.show', $bankTransfer)
            ->with('success', 'Transferencia anulada.');
    }
}
