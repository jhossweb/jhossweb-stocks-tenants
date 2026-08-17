<?php

namespace App\Services\Finance;

use App\Models\CashClosing;
use App\Models\User;
use App\Repositories\Finance\CashClosingRepository;

class CashClosingService
{
    public function __construct(
        protected CashClosingRepository $repo,
    ) {}

    public function calculatePreview(string $date, float $openingBalance): array
    {
        $salesTotal = $this->repo->getCashSalesTotal($date);
        $receivedTotal = $this->repo->getReceivedTotal($date);

        return [
            'cc_date' => $date,
            'cc_opening_balance' => $openingBalance,
            'cc_total_sales' => $salesTotal,
            'cc_total_received' => $receivedTotal,
            'cc_total_manual_inflows' => 0,
            'cc_total_manual_outflows' => 0,
            'cc_expected_closing' => null,
            'cc_counted_amount' => null,
            'cc_difference' => null,
            'cc_status' => 'balanced',
            'receipts_by_method' => $this->repo->getReceivedByMethod($date),
            'receipts_details' => $this->repo->getReceivedDetails($date),
            'grouped_receipts' => $this->repo->getGroupedReceipts($date),
            'vuelto_total' => $this->repo->getVueltoTotal($date),
            'vuelto_details' => $this->repo->getVueltoDetails($date),
            'grouped_vueltos' => $this->repo->getGroupedVueltos($date),
        ];
    }

    public function storeClosing(
        string $date,
        float $openingBalance,
        ?string $notes,
        array $movementsData,
        User $user,
        array $countedDetails,
        array $differences,
    ): CashClosing {
        $salesTotal = $this->repo->getCashSalesTotal($date);
        $receivedTotal = $this->repo->getReceivedTotal($date);

        $manualInflows = 0;
        $manualOutflows = 0;

        $movements = collect();

        foreach ($movementsData as $m) {
            $amount = (float) $m['cm_amount'];
            if ($m['cm_type'] === 'inflow') {
                $manualInflows += $amount;
            } else {
                $manualOutflows += $amount;
            }

            $movements->push([
                'cm_type' => $m['cm_type'],
                'cm_amount' => $amount,
                'cm_description' => $m['cm_description'],
                'cm_reference' => $m['cm_reference'] ?? null,
            ]);
        }

        $anyDifference = collect($differences)->contains(fn ($diff) => (float) $diff !== 0.0);

        $closingData = [
            'cc_date' => $date,
            'cc_opening_balance' => $openingBalance,
            'cc_total_sales' => $salesTotal,
            'cc_total_received' => $receivedTotal,
            'cc_total_manual_inflows' => $manualInflows,
            'cc_total_manual_outflows' => $manualOutflows,
            'cc_expected_closing' => null,
            'cc_counted_amount' => null,
            'cc_difference' => null,
            'cc_status' => $anyDifference ? 'discrepancy' : 'balanced',
            'cc_notes' => $notes,
            'cc_closed_by' => $user->id,
            'cc_closed_at' => now(),
            'cc_counted_details' => $countedDetails,
            'cc_differences' => $differences,
        ];

        return $this->repo->storeClosing($closingData, $movements);
    }

    /**
     * @return array{receipts_by_method: array<string, float>, receipts_details: array<int, array{coin: string, amount: float, method: string, reference: string, payment_date: string, document: string, customer: string, bank: string}>, grouped_receipts: array<string, array<string, float>>, vuelto_total: float, vuelto_details: array<int, array{coin: string, amount: float, method: string, reference: string, payment_date: string, document: string, customer: string, bank: string}>, grouped_vueltos: array<string, array<string, float>>}
     */
    public function computeBreakdown(string $date): array
    {
        return [
            'receipts_by_method' => $this->repo->getReceivedByMethod($date),
            'receipts_details' => $this->repo->getReceivedDetails($date),
            'grouped_receipts' => $this->repo->getGroupedReceipts($date),
            'vuelto_total' => $this->repo->getVueltoTotal($date),
            'vuelto_details' => $this->repo->getVueltoDetails($date),
            'grouped_vueltos' => $this->repo->getGroupedVueltos($date),
        ];
    }
}
