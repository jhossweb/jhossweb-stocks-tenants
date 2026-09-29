<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

readonly class InventoryAdjustmentDTO
{
    /**
     * @param  array<int, InventoryAdjustmentItemDTO>  $items
     */
    public function __construct(
        public string $number,
        public string $date,
        public ?string $notes,
        public array $items,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            number: (string) $data['inv_number'],
            date: (string) $data['inv_date'],
            notes: $data['inv_notes'] ?? null,
            items: array_values(array_map(
                static fn (array $item): InventoryAdjustmentItemDTO => InventoryAdjustmentItemDTO::fromArray($item),
                $data['items'] ?? [],
            )),
        );
    }
}
