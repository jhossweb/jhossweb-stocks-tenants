<?php

namespace App\DTOs\Payroll;

readonly class PayrollPaymentDTO
{
    /**
     * @param  array<int, array{pay_id: int, amount: float}>  $payments
     */
    public function __construct(
        public string $paymentDate,
        public int $employeeId,
        public int $bankAccountId,
        public string $paymentCurrency,
        public float $paymentRate,
        public string $paymentMethod,
        public string $paymentReference,
        public ?string $paymentDescription,
        public array $payments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentDate: $data['payment_date'],
            employeeId: (int) $data['employee_id'],
            bankAccountId: (int) $data['bank_account_id'],
            paymentCurrency: $data['payment_currency'],
            paymentRate: (float) $data['payment_rate'],
            paymentMethod: $data['payment_method'],
            paymentReference: $data['payment_reference'],
            paymentDescription: $data['payment_description'] ?? null,
            payments: $data['payments'],
        );
    }
}
