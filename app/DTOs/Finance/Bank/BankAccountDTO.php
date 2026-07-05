<?php

namespace App\DTOs\Finance\Bank;

readonly class BankAccountDTO
{
    public function __construct(
        public string $bankName,
        public string $bankAccountNumber,
        public string $bankType,
        public string $bankCurrency,
        public bool $bankStatus,
        public ?string $bankDescription,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bankName: $data['bank_name'],
            bankAccountNumber: $data['bank_account_number'],
            bankType: $data['bank_type'],
            bankCurrency: $data['bank_currency'],
            bankStatus: (bool) $data['bank_status'],
            bankDescription: $data['bank_description'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_type' => $this->bankType,
            'bank_currency' => $this->bankCurrency,
            'bank_status' => $this->bankStatus,
            'bank_description' => $this->bankDescription,
        ];
    }
}
