<?php

namespace App\DTOs\Finance\Cobranza;

use App\Models\Rate;

readonly class StoreReceiptDTO
{
    /** @param ReceiptPaymentDTO[] $payments */
    public function __construct(
        public string $paymentDate,
        public int $customerId,
        public int $bankAccountId,
        public string $paymentCurrency,
        public Rate $paymentRate,
        public string $paymentMethod,
        public string $paymentReference,
        public ?string $paymentDescription,
        public array $payments,
    ) {}

    public static function fromRequest(array $validated, Rate $paymentRate): self
    {
        return new self(
            paymentDate: $validated['payment_date'],
            customerId: (int) $validated['customer_id'],
            bankAccountId: (int) $validated['bank_account_id'],
            paymentCurrency: $validated['payment_currency'],
            paymentRate: $paymentRate,
            paymentMethod: $validated['payment_method'],
            paymentReference: $validated['payment_reference'],
            paymentDescription: $validated['payment_description'] ?? null,
            payments: array_map(
                static fn (array $p) => ReceiptPaymentDTO::fromArray($p),
                $validated['payments'],
            ),
        );
    }
}
