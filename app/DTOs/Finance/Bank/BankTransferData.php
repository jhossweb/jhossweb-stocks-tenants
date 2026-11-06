<?php

declare(strict_types=1);

namespace App\DTOs\Finance\Bank;

readonly class BankTransferData
{
    public function __construct(
        public string $date,
        public int $sourceBankAccountId,
        public int $destinationBankAccountId,
        public float $amount,
        public ?string $reference = null,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            date: (string) $data['bt_date'],
            sourceBankAccountId: (int) $data['bt_source_bank_account_id'],
            destinationBankAccountId: (int) $data['bt_destination_bank_account_id'],
            amount: (float) $data['bt_amount'],
            reference: isset($data['bt_reference']) && $data['bt_reference'] !== '' ? (string) $data['bt_reference'] : null,
            description: isset($data['bt_description']) && $data['bt_description'] !== '' ? (string) $data['bt_description'] : null,
        );
    }
}
