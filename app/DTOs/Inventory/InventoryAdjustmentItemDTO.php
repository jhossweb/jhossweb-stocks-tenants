<?php

declare(strict_types=1);

namespace App\DTOs\Inventory;

readonly class InventoryAdjustmentItemDTO
{
    public function __construct(
        public ?int $id,
        public string $proCode,
        public int $warehouseId,
        public float $quantity,
        public int $reasonId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            proCode: (string) $data['min_pro_code'],
            warehouseId: (int) $data['min_war_id'],
            quantity: (float) $data['min_quantity'],
            reasonId: (int) $data['min_reason_id'],
        );
    }

    /**
     * Atributos de `movements_inventories` para este renglón.
     *
     * @return array<string, mixed>
     */
    public function toMovementAttributes(string $inventoryNumber, string $transTypeCode, int $factor): array
    {
        return [
            'min_inv_number' => $inventoryNumber,
            'min_trans_type_code' => $transTypeCode,
            'min_factor' => $factor,
            'min_pro_code' => $this->proCode,
            'min_war_id' => $this->warehouseId,
            'min_quantity' => $this->quantity,
            'min_reason_id' => $this->reasonId,
        ];
    }
}
