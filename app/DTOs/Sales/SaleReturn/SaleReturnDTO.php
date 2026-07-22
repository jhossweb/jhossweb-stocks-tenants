<?php

namespace App\DTOs\Sales\SaleReturn;

readonly class SaleReturnDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public string $slsNumber,
        public string $slsDate,
        public int $slsCustomerId,
        public float $slsValorcam,
        public string $slsCoin,
        public ?string $slsReference,
        public float $slsSubtotal,
        public float $slsExento,
        public float $slsIva,
        public float $slsTotal,
        public string $slsPaymentCondition,
        public int $slsCreditDays,
        public string $slsDueDate,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slsNumber: $data['sls_number'],
            slsDate: $data['sls_date'],
            slsCustomerId: (int) $data['sls_customer_id'],
            slsValorcam: (float) $data['sls_valorcam'],
            slsCoin: $data['sls_coin'],
            slsReference: $data['sls_reference'] ?? null,
            slsSubtotal: (float) $data['sls_subtotal'],
            slsExento: (float) $data['sls_exento'],
            slsIva: (float) $data['sls_iva'],
            slsTotal: (float) $data['sls_total'],
            slsPaymentCondition: $data['sls_payment_condition'] ?? 'cash',
            slsCreditDays: (int) ($data['sls_credit_days'] ?? 0),
            slsDueDate: $data['sls_due_date'] ?? $data['sls_date'],
            items: $data['items'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sls_number' => $this->slsNumber,
            'sls_date' => $this->slsDate,
            'sls_customer_id' => $this->slsCustomerId,
            'sls_valorcam' => $this->slsValorcam,
            'sls_coin' => $this->slsCoin,
            'sls_reference' => $this->slsReference,
            'sls_subtotal' => $this->slsSubtotal,
            'sls_exento' => $this->slsExento,
            'sls_iva' => $this->slsIva,
            'sls_total' => $this->slsTotal,
            'sls_payment_condition' => $this->slsPaymentCondition,
            'sls_credit_days' => $this->slsCreditDays,
            'sls_due_date' => $this->slsDueDate,
        ];
    }
}
