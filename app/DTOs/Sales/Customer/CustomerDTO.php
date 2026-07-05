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
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cltName: $data['clt_name'],
            cltAddress: $data['clt_address'],
            cltPhone: $data['clt_phone'],
            cltEmail: $data['clt_email'],
            cltWebsite: $data['clt_website'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'clt_name' => $this->cltName,
            'clt_address' => $this->cltAddress,
            'clt_phone' => $this->cltPhone,
            'clt_email' => $this->cltEmail,
            'clt_website' => $this->cltWebsite,
        ];
    }
}
