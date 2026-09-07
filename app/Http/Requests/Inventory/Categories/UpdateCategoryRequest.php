<?php

namespace App\Http\Requests\Inventory\Categories;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
            'cat_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'cat_name')->ignore($this->route('category')),
            ],
        ];
    }
}
