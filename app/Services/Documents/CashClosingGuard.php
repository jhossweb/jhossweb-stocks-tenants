<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\AccountPayments;
use App\Models\CashClosing;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SaleOrder;
use Illuminate\Database\Eloquent\Model;

class CashClosingGuard
{
    public function assertNotInClosedCashClosing(Model $document): void
    {
        $date = $this->dateFor($document);

        if ($date === null) {
            return;
        }

        $closed = CashClosing::query()
            ->whereDate('cc_date', $date)
            ->whereNotNull('cc_closed_at')
            ->exists();

        if ($closed) {
            throw new \DomainException("No puedes anular el documento porque su fecha ({$date}) ya tiene un cierre de caja cerrado.");
        }
    }

    private function dateFor(Model $document): ?string
    {
        return match (true) {
            $document instanceof SaleOrder => $document->sls_date?->format('Y-m-d'),
            $document instanceof PurchaseOrder => $document->invoice_date?->format('Y-m-d'),
            $document instanceof Receipt, $document instanceof AccountPayments => $document->bankTransaction?->btr_transaction_date?->format('Y-m-d'),
            $document instanceof Inventory => $document->inv_date?->format('Y-m-d'),
            $document instanceof Expense => $document->exp_date?->format('Y-m-d'),
            default => null,
        };
    }
}
