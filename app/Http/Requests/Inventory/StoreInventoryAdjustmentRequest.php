<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'inv_number' => 'required|string|max:50|unique:inventories,inv_number',
            'inv_date' => 'required|date',
            'inv_notes' => 'nullable|string',

            'items' => 'required|array|min:1',
            'items.*.min_pro_code' => 'required|exists:products,pro_code',
            'items.*.min_war_id' => 'required|exists:warehouses,id',
            'items.*.min_quantity' => 'required|numeric|min:0.01',
            'items.*.min_reason_id' => 'required|exists:adjustment_reasons,id',
        ];
    }
}
