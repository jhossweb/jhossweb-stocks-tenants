<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\DTOs\Inventory\InventoryAdjustmentDTO;
use App\DTOs\Inventory\InventoryAdjustmentItemDTO;
use App\Models\Inventory;
use App\Models\MovementInventory;
use App\Models\TransactionType;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Flujo de ajustes de inventario (trans_type_code INV).
 *
 * Todo el flujo va en transacción: los movimientos dispara
 * MovInventoryObserver, que muta `stocks`. Si un renglón falla a la mitad,
 * el stock quedaría descuadrado sin el rollback.
 */
class InventoryAdjustmentService
{
    public function create(InventoryAdjustmentDTO $dto): Inventory
    {
        $transactionType = $this->transactionType();

        return DB::transaction(function () use ($dto, $transactionType): Inventory {
            $adjustment = new Inventory;
            $adjustment->inv_number = $dto->number;
            $adjustment->inv_date = $dto->date;
            $adjustment->inv_trans_type_code = $transactionType->trans_type_code;
            $adjustment->inv_notes = $dto->notes;
            $adjustment->inv_status = 'completed';
            $adjustment->save();

            $this->syncMovements($adjustment, $transactionType, $dto->items);

            return $adjustment;
        });
    }

    public function update(Inventory $adjustment, InventoryAdjustmentDTO $dto): Inventory
    {
        $transactionType = $this->transactionType();

        return DB::transaction(function () use ($adjustment, $dto, $transactionType): Inventory {
            // `inv_number` NO se toca: es clave natural referenciada por
            // movements_inventories y la FK no tiene ON UPDATE CASCADE.
            // UpdateInventoryAdjustmentRequest lo declara inmutable.
            $adjustment->update([
                'inv_date' => $dto->date,
                'inv_notes' => $dto->notes,
            ]);

            $this->syncMovements($adjustment, $transactionType, $dto->items);

            return $adjustment;
        });
    }

    public function delete(Inventory $adjustment): void
    {
        DB::transaction(function () use ($adjustment): void {
            // Uno por uno y NUNCA con whereIn()->delete(): los mass deletes de
            // EloquentBuilder no emiten eventos de modelo, así que
            // MovInventoryObserver::deleted() no correría y el stock
            // descontado por el ajuste jamás se devolvería al inventario.
            $adjustment->movements->each(
                static fn (MovementInventory $movement) => $movement->delete(),
            );

            $adjustment->delete();
        });
    }

    /**
     * Reconcilia los movimientos con los renglones enviados: actualiza los que
     * traen id, crea los nuevos y elimina los que sobran.
     *
     * @param  array<int, InventoryAdjustmentItemDTO>  $items
     */
    private function syncMovements(Inventory $adjustment, TransactionType $transactionType, array $items): void
    {
        /** @var array<int, MovementInventory> $current */
        $current = $adjustment->movements()->get()->keyBy('id');
        $keptIds = [];

        foreach ($items as $item) {
            $movement = $item->id !== null ? $current->get($item->id) : null;

            $attributes = $item->toMovementAttributes(
                inventoryNumber: (string) $adjustment->inv_number,
                transTypeCode: (string) $adjustment->inv_trans_type_code,
                factor: (int) $transactionType->trans_type_factor,
            );

            if (! $movement instanceof MovementInventory) {
                $movement = MovementInventory::create($attributes);
                $keptIds[] = (int) $movement->id;

                continue;
            }

            $keptIds[] = (int) $movement->id;

            // Solo se guarda si algo cambió de verdad: un save() sin cambios
            // igual dispara `updated`, que revierte y reaplica stock.
            $needsSave = (string) $movement->min_inv_number !== $attributes['min_inv_number']
                || (int) $movement->min_reason_id !== $attributes['min_reason_id']
                || $this->hasStockAffectingChanges($movement, $attributes);

            if ($needsSave) {
                $movement->fill($attributes)->save();
            }
        }

        $current
            ->reject(static fn (MovementInventory $movement): bool => in_array((int) $movement->id, $keptIds, true))
            ->each(static fn (MovementInventory $movement) => $movement->delete());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function hasStockAffectingChanges(MovementInventory $movement, array $attributes): bool
    {
        return (string) $movement->min_pro_code !== $attributes['min_pro_code']
            || (int) $movement->min_war_id !== $attributes['min_war_id']
            || (float) $movement->min_quantity !== $attributes['min_quantity']
            || (int) $movement->min_factor !== $attributes['min_factor']
            || (string) $movement->min_trans_type_code !== $attributes['min_trans_type_code'];
    }

    private function transactionType(): TransactionType
    {
        $transactionType = TransactionType::query()
            ->where('trans_type_code', InventoryAdjustmentRepository::TRANS_TYPE_CODE)
            ->first();

        if (! $transactionType instanceof TransactionType) {
            throw new RuntimeException(
                'Tipo de transacción INV no encontrado. Ejecutá TransactionTypeSeeder.'
            );
        }

        return $transactionType;
    }
}
