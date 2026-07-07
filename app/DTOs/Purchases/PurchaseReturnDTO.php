<?php

namespace App\DTOs\Purchases;

readonly class PurchaseReturnDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public string $invoiceNumber,
        public string $invoiceDate,
        public int $invoiceSupplierId,
        public float $invoiceValorcam,
        public string $invoiceCoin,
        public float $invoiceSubtotal,
        public float $invoiceExento,
        public float $invoiceIva,
        public float $invoiceTotal,
        public string $invoiceReference,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            invoiceNumber: $data['invoice_number'],
            invoiceDate: $data['invoice_date'],
            invoiceSupplierId: (int) $data['invoice_supplier_id'],
            invoiceValorcam: (float) $data['invoice_valorcam'],
            invoiceCoin: $data['invoice_coin'],
            invoiceSubtotal: (float) $data['invoice_subtotal'],
            invoiceExento: (float) $data['invoice_exento'],
            invoiceIva: (float) $data['invoice_iva'],
            invoiceTotal: (float) $data['invoice_total'],
            invoiceReference: $data['invoice_reference'],
            items: $data['items'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoiceNumber,
            'invoice_date' => $this->invoiceDate,
            'invoice_supplier_id' => $this->invoiceSupplierId,
            'invoice_valorcam' => $this->invoiceValorcam,
            'invoice_coin' => $this->invoiceCoin,
            'invoice_subtotal' => $this->invoiceSubtotal,
            'invoice_exento' => $this->invoiceExento,
            'invoice_iva' => $this->invoiceIva,
            'invoice_total' => $this->invoiceTotal,
            'invoice_reference' => $this->invoiceReference,
        ];
    }
}
