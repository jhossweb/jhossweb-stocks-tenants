<?php

namespace App\DTOs\Sales\Quote;

readonly class QuoteDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public string $slsDate,
        public int $slsCustomerId,
        public float $slsValorcam,
        public string $slsCoin,
        public float $slsSubtotal,
        public float $slsExento,
        public float $slsIva,
        public float $slsTotal,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slsDate: $data['sls_date'],
            slsCustomerId: (int) $data['sls_customer_id'],
            slsValorcam: (float) $data['sls_valorcam'],
            slsCoin: $data['sls_coin'],
            slsSubtotal: (float) $data['sls_subtotal'],
            slsExento: (float) $data['sls_exento'],
            slsIva: (float) $data['sls_iva'],
            slsTotal: (float) $data['sls_total'],
            items: $data['items'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sls_date' => $this->slsDate,
            'sls_customer_id' => $this->slsCustomerId,
            'sls_valorcam' => $this->slsValorcam,
            'sls_coin' => $this->slsCoin,
            'sls_subtotal' => $this->slsSubtotal,
            'sls_exento' => $this->slsExento,
            'sls_iva' => $this->slsIva,
            'sls_total' => $this->slsTotal,
        ];
    }
}
