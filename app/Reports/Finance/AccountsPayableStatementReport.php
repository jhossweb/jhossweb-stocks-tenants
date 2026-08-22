<?php

namespace App\Reports\Finance;

use App\Models\Supplier;
use App\Reports\PdfReport;
use App\Repositories\Purchases\AccountsPayable\AccountsPayableRepository;
use Illuminate\Http\Response;

class AccountsPayableStatementReport extends PdfReport
{
    public function __construct(
        protected AccountsPayableRepository $payableRepository,
    ) {}

    public function download(Supplier $supplier): Response
    {
        $accounts = $this->payableRepository->getSupplierPending($supplier);
        $accounts->each(fn ($account) => $account->purchaseOrder?->append('days_remaining'));

        $totals = [];
        foreach ($accounts as $account) {
            $coin = $account->purchaseOrder?->invoice_coin ?? '—';
            $totals[$coin] = round(($totals[$coin] ?? 0) + (float) $account->acp_balance, 4);
        }

        $data = [
            'supplier' => $supplier,
            'accounts' => $accounts,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];

        return $this->make('pdf.estado-cxp', $data, $this->buildFilename('estado_cxp', (string) $supplier->supplier_name));
    }
}
