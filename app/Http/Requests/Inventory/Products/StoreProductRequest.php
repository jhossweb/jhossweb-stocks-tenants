<?php

namespace App\Http\Requests\Inventory\Products;

use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pro_code' => ['required', 'string', 'max:50', 'unique:products,pro_code'],
            'pro_name' => ['required', 'string', 'max:200', 'unique:products,pro_name'],
            'pro_und' => ['required', Rule::in(UndProduct::values())],
            'pro_iva' => ['required', Rule::in(TypeIva::values())],
            'pro_cat_id' => ['required', 'exists:categories,id'],
            'pro_bra_id' => ['required', 'exists:brands,id'],
        ];
    }
}
