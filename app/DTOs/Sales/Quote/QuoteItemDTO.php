<?php

namespace App\DTOs\Sales\Quote;

readonly class QuoteItemDTO
{
    public function __construct(
        public string $mvsProCode,
        public int $mvsWarId,
        public float $mvsQuantity,
        public float $mvsUnitPrice,
        public float $mvsUnitPriceRef,
        public float $mvsProIva,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mvsProCode: $data['mvs_pro_code'],
            mvsWarId: (int) $data['mvs_war_id'],
            mvsQuantity: (float) $data['mvs_quantity'],
            mvsUnitPrice: (float) $data['mvs_unit_price'],
            mvsUnitPriceRef: (float) $data['mvs_unit_price_ref'],
            mvsProIva: (float) $data['mvs_pro_iva'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'mvs_pro_code' => $this->mvsProCode,
            'mvs_war_id' => $this->mvsWarId,
            'mvs_quantity' => $this->mvsQuantity,
            'mvs_unit_price' => $this->mvsUnitPrice,
            'mvs_unit_price_ref' => $this->mvsUnitPriceRef,
            'mvs_pro_iva' => $this->mvsProIva,
        ];
    }
}
