<?php

namespace App\Services\Payroll;

use App\DTOs\Payroll\PayrollPaymentDTO;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollPaymentService
{
    /**
     * @return array{success: bool, message?: string}
     */
    public function pay(PayrollPaymentDTO $dto): array
    {
        $activePayments = array_filter($dto->payments, fn ($p) => $p['amount'] > 0);

        if (empty($activePayments)) {
            return ['success' => false, 'message' => 'Debe ingresar al menos un monto a pagar.'];
        }

        try {
            DB::beginTransaction();

            foreach ($activePayments as $paymentData) {
                $payroll = Payroll::with('employee')->findOrFail($paymentData['pay_id']);

                $payroll->registerPayment(
                    amountInBankCurrency: $paymentData['amount'],
                    bankAccountId: $dto->bankAccountId,
                    reference: $dto->paymentReference,
                    description: $dto->paymentDescription ?: 'Pago de nómina',
                    paymentCurrency: $dto->paymentCurrency,
                    paymentRate: $dto->paymentRate,
                    paymentMethod: $dto->paymentMethod,
                    paymentDate: $dto->paymentDate,
                );

                $bankTransaction = $payroll->payments()->latest()->first();
                if ($bankTransaction) {
                    $bankTransaction->update([
                        'btr_valorcam' => $dto->paymentRate,
                        'btr_coin' => $dto->paymentCurrency,
                    ]);
                }
            }

            DB::commit();

            return ['success' => true];
        } catch (\Exception $e) {
            DB::rollBack();

            return ['success' => false, 'message' => 'Error al procesar el pago: '.$e->getMessage()];
        }
    }
}
