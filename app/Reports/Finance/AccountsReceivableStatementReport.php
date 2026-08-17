<?php

namespace App\Reports\Finance;

use App\Models\Customer;
use App\Reports\PdfReport;
use App\Repositories\Sales\AccountsReceivable\AccountsReceivableRepository;
use Illuminate\Http\Response;

class AccountsReceivableStatementReport extends PdfReport
{
    public function __construct(
        protected AccountsReceivableRepository $receivableRepository,
    ) {}

    public function download(Customer $customer): Response
    {
        $accounts = $this->receivableRepository->getClientCreditPending($customer);
        $accounts->each(fn ($account) => $account->saleOrder?->append('days_remaining'));

        $totals = [];
        foreach ($accounts as $account) {
            $coin = $account->saleOrder?->sls_coin ?? '—';
            $totals[$coin] = round(($totals[$coin] ?? 0) + (float) $account->acr_balance, 4);
        }

        $data = [
            'customer' => $customer,
            'accounts' => $accounts,
            'totals' => $totals,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];

        $detail = implode('_', array_filter([
            $customer->clt_code,
            $customer->clt_name,
        ]));

        return $this->make('pdf.estado-cxc', $data, $this->buildFilename('estado_cxc', $detail));
    }
}
