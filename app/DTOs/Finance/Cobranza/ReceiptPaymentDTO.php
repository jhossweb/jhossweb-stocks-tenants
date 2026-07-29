<?php

namespace App\DTOs\Finance\Cobranza;

readonly class ReceiptPaymentDTO
{
    public function __construct(
        public int $acrId,
        public float $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            acrId: (int) $data['acr_id'],
            amount: (float) $data['amount'],
        );
    }
}
