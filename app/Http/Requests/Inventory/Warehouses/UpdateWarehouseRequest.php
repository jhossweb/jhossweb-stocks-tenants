<?php

namespace App\Http\Requests\Inventory\Warehouses;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
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
            'war_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('warehouses', 'war_name')->ignore($this->route('warehouse')),
            ],
        ];
    }
}
