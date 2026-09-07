<?php

namespace App\Http\Requests\Inventory\Warehouses;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
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
            'war_name' => ['required', 'string', 'max:100', 'unique:warehouses,war_name'],
        ];
    }
}
