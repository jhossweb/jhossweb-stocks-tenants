<?php

namespace App\Http\Requests\Inventory\Brands;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
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
            'bra_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('brands', 'bra_name')->ignore($this->route('brand')),
            ],
        ];
    }
}
