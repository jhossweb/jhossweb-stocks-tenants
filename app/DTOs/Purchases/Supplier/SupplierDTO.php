<?php

namespace App\DTOs\Purchases\Supplier;

readonly class SupplierDTO
{
    public function __construct(
        public string $supplierName,
        public string $supplierAddress,
        public string $supplierPhone,
        public string $supplierEmail,
        public ?string $supplierWebsite,
        public int $supplierCreditDays,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            supplierName: $data['supplier_name'],
            supplierAddress: $data['supplier_address'],
            supplierPhone: $data['supplier_phone'],
            supplierEmail: $data['supplier_email'],
            supplierWebsite: $data['supplier_website'] ?? null,
            supplierCreditDays: (int) ($data['supplier_credit_days'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'supplier_name' => $this->supplierName,
            'supplier_address' => $this->supplierAddress,
            'supplier_phone' => $this->supplierPhone,
            'supplier_email' => $this->supplierEmail,
            'supplier_website' => $this->supplierWebsite,
            'supplier_credit_days' => $this->supplierCreditDays,
        ];
    }
}
