<?php

namespace App\Observers\Sales;

use App\Enum\Sales\StatusSales;
use App\Models\AccountsReceivable;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Services\Dashboard\DashboardCacheInvalidator;

class SaleOrderObserver
{
    public function created(SaleOrder $saleOrder): void
    {
        DashboardCacheInvalidator::receivables($saleOrder->sls_date?->format('Y-m'));

        if ($saleOrder->sls_trans_type_code === 'FACV') {
            $rate = Rate::where('rate_date', $saleOrder->sls_date ?? $saleOrder->created_at?->format('Y-m-d'))->first()
                ?? Rate::orderBy('rate_date', 'desc')->first();

            $totalUsd = $rate
                ? Rate::convert($saleOrder->sls_total, $saleOrder->sls_coin, 'USD', $rate)
                : $saleOrder->sls_total;

            AccountsReceivable::create([
                'acr_sls_number' => $saleOrder->sls_number,
                'acr_total' => $saleOrder->sls_total,
                'acr_balance' => $saleOrder->sls_total,
                'acr_total_usd' => $totalUsd,
                'acr_balance_usd' => $totalUsd,
                'acr_status' => StatusSales::PENDING->value,
            ]);
        }
    }

    public function updated(SaleOrder $saleOrder): void
    {
        DashboardCacheInvalidator::receivables($saleOrder->sls_date?->format('Y-m'));

        $accountsReceivable = $saleOrder->accountsReceivable;
        if ($accountsReceivable) {
            $difference = $saleOrder->sls_total - $saleOrder->getOriginal('sls_total');
            $accountsReceivable->acr_total = $saleOrder->sls_total;
            $accountsReceivable->acr_balance += $difference;

            $rate = Rate::where('rate_date', $saleOrder->sls_date ?? $saleOrder->created_at?->format('Y-m-d'))->first()
                ?? Rate::orderBy('rate_date', 'desc')->first();

            if ($rate) {
                $diffUsd = Rate::convert($difference, $saleOrder->sls_coin, 'USD', $rate);
                if ($accountsReceivable->acr_balance_usd !== null) {
                    $accountsReceivable->acr_balance_usd += $diffUsd;
                }
                $accountsReceivable->acr_total_usd = Rate::convert($saleOrder->sls_total, $saleOrder->sls_coin, 'USD', $rate);
            }

            if ($accountsReceivable->acr_balance <= 0) {
                $accountsReceivable->acr_status = StatusSales::PAID->value;
            } elseif ($accountsReceivable->acr_balance < $accountsReceivable->acr_total) {
                $accountsReceivable->acr_status = StatusSales::PARTIAL->value;
            } else {
                $accountsReceivable->acr_status = StatusSales::PENDING->value;
            }
            $accountsReceivable->save();
        }
    }

    public function deleted(SaleOrder $saleOrder): void
    {
        DashboardCacheInvalidator::receivables($saleOrder->sls_date?->format('Y-m'));
        $saleOrder->accountsReceivable()?->delete();
    }
}
