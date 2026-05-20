<?php

namespace App\Http\Requests\Inventory\Products;

use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pro_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'pro_code')->ignore($this->route('product')),
            ],
            'pro_name' => [
                'required',
                'string',
                'max:200',
                Rule::unique('products', 'pro_name')->ignore($this->route('product')),
            ],
            'pro_und' => ['required', Rule::in(UndProduct::values())],
            'pro_iva' => ['required', Rule::in(TypeIva::values())],
            'pro_cat_id' => ['required', 'exists:categories,id'],
            'pro_bra_id' => ['required', 'exists:brands,id'],
        ];
    }
}
