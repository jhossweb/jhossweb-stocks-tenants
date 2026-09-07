<?php

namespace App\Http\Requests\Inventory\Brands;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
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
            'bra_name' => ['required', 'string', 'max:100', 'unique:brands,bra_name'],
        ];
    }
}
