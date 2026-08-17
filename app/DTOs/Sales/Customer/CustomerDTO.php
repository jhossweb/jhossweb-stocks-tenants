<?php

namespace App\DTOs\Sales\Customer;

readonly class CustomerDTO
{
    public function __construct(
        public string $cltName,
        public string $cltAddress,
        public string $cltPhone,
        public string $cltEmail,
        public ?string $cltWebsite,
        public int $cltCreditDays,
        public ?string $cltCode = null,
        public ?string $cltCedula = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cltName: $data['clt_name'],
            cltAddress: $data['clt_address'],
            cltPhone: $data['clt_phone'],
            cltEmail: $data['clt_email'],
            cltWebsite: $data['clt_website'] ?? null,
            cltCreditDays: (int) ($data['clt_credit_days'] ?? 0),
            cltCode: $data['clt_code'] ?? null,
            cltCedula: $data['clt_cedula'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'clt_code' => $this->cltCode,
            'clt_cedula' => $this->cltCedula,
            'clt_name' => $this->cltName,
            'clt_address' => $this->cltAddress,
            'clt_phone' => $this->cltPhone,
            'clt_email' => $this->cltEmail,
            'clt_website' => $this->cltWebsite,
            'clt_credit_days' => $this->cltCreditDays,
        ];
    }
}
