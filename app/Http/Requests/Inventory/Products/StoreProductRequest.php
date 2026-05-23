<?php

namespace App\Http\Requests\Inventory\Products;

use App\Enum\Inventary\ProType;
use App\Enum\Inventary\UndProduct;
use App\Enum\TypeIva;
use Illuminate\Contracts\Validation\ValidationRule;
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pro_code' => ['required', 'string', 'max:50', 'unique:products,pro_code'],
            'pro_name' => ['required', 'string', 'max:200', 'unique:products,pro_name'],
            'pro_und' => ['required', Rule::in(UndProduct::values())],
            'pro_conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'pro_cant_min' => ['nullable', 'numeric', 'min:0'],
            'pro_iva' => ['required', Rule::in(TypeIva::values())],
            'pro_cat_id' => ['required', 'exists:categories,id'],
            'pro_bra_id' => ['required', 'exists:brands,id'],
            'pro_type' => ['required', Rule::in(ProType::values())],
            'components' => ['nullable', 'array', 'required_if:pro_type,compuesto'],
            'components.*.cp_comp_pro_code' => ['required', 'string', 'exists:products,pro_code', 'different:pro_code'],
            'components.*.cp_pro_und' => ['required', Rule::in(UndProduct::values())],
            'components.*.cp_quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }
}
