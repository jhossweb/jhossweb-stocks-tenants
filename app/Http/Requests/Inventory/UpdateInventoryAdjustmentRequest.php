<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\DTOs\Inventory\InventoryAdjustmentDTO;
use App\Models\Inventory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // `inv_number` es clave natural referenciada por
            // `movements_inventories.min_inv_number` con una FK sin
            // ON UPDATE CASCADE: renombrar un ajuste ya guardado revienta a
            // nivel BD y, si se tolerara, dejaría los movimientos huérfanos.
            // El número queda inmutable desde la creación; se documentaría
            // ON UPDATE CASCADE en una migración si hay que permitirlo.
            'inv_number' => ['required', 'string', 'max:50', Rule::in([$this->currentNumber()])],
            'inv_date' => ['required', 'date'],
            'inv_notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:movements_inventories,id'],
            'items.*.min_pro_code' => ['required', 'string', 'exists:products,pro_code'],
            'items.*.min_war_id' => ['required', 'integer', 'exists:warehouses,id'],
            'items.*.min_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.min_reason_id' => ['required', 'integer', 'exists:adjustment_reasons,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'inv_number.in' => 'El número de un ajuste guardado no se puede modificar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inv_number' => 'número de ajuste',
            'inv_date' => 'fecha',
            'inv_notes' => 'notas',
            'items' => 'renglones',
        ];
    }

    public function toDto(): InventoryAdjustmentDTO
    {
        return InventoryAdjustmentDTO::fromValidated($this->validated());
    }

    private function currentNumber(): ?string
    {
        $adjustment = $this->route('adjustment');

        if (is_numeric($adjustment)) {
            return Inventory::query()->whereKey((int) $adjustment)->value('inv_number');
        }

        return $adjustment instanceof Inventory ? $adjustment->inv_number : null;
    }
}
