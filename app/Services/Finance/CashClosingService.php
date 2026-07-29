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
        $receivedTotal = $this->repo->getCashReceivedTotal($date);

        return [
            'cc_date' => $date,
            'cc_opening_balance' => $openingBalance,
            'cc_total_sales' => $salesTotal,
            'cc_total_received' => $receivedTotal,
            'cc_total_manual_inflows' => 0,
            'cc_total_manual_outflows' => 0,
            'cc_expected_closing' => round($openingBalance + $salesTotal + $receivedTotal, 4),
            'cc_counted_amount' => 0,
            'cc_difference' => 0,
            'cc_status' => 'balanced',
        ];
    }

    public function storeClosing(string $date, float $openingBalance, float $countedAmount, ?string $notes, array $movementsData, User $user): CashClosing
    {
        $salesTotal = $this->repo->getCashSalesTotal($date);
        $receivedTotal = $this->repo->getCashReceivedTotal($date);

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

        $expected = round($openingBalance + $salesTotal + $receivedTotal + $manualInflows - $manualOutflows, 4);
        $difference = round($countedAmount - $expected, 4);

        $closingData = [
            'cc_date' => $date,
            'cc_opening_balance' => $openingBalance,
            'cc_total_sales' => $salesTotal,
            'cc_total_received' => $receivedTotal,
            'cc_total_manual_inflows' => $manualInflows,
            'cc_total_manual_outflows' => $manualOutflows,
            'cc_expected_closing' => $expected,
            'cc_counted_amount' => $countedAmount,
            'cc_difference' => $difference,
            'cc_status' => $difference === 0.0 ? 'balanced' : 'discrepancy',
            'cc_notes' => $notes,
            'cc_closed_by' => $user->id,
            'cc_closed_at' => now(),
        ];

        return $this->repo->storeClosing($closingData, $movements);
    }
}
