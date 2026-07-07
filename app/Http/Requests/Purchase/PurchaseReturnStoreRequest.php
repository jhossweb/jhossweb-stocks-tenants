<?php

namespace App\Http\Requests\Purchase;

use App\Enum\TypeCoin;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseReturnStoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invoice_number' => ['required', 'string', 'max:50', 'unique:purchase_orders,invoice_number'],
            'invoice_date' => ['required', 'date'],
            'invoice_supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_valorcam' => ['required', 'numeric'],
            'invoice_coin' => ['required', 'string', Rule::in(TypeCoin::values())],
            'invoice_subtotal' => ['required', 'numeric'],
            'invoice_exento' => ['required', 'numeric'],
            'invoice_iva' => ['required', 'numeric'],
            'invoice_total' => ['required', 'numeric'],
            'invoice_reference' => ['required', 'string', 'exists:purchase_orders,invoice_number'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.mov_pro_code' => ['required', 'exists:products,pro_code'],
            'items.*.mov_war_id' => ['required', 'exists:warehouses,id'],
            'items.*.mov_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.mov_unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.mov_unit_cost_ref' => ['required', 'numeric', 'min:0'],
            'items.*.mov_pro_iva' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $data = $validator->validated();

            $originalPurchase = PurchaseOrder::where('invoice_number', $data['invoice_reference'])->first();

            if (! $originalPurchase) {
                $validator->errors()->add('invoice_reference', 'Factura origen no encontrada.');

                return;
            }

            $returnedMovements = $originalPurchase->returnedMovements;

            foreach ($data['items'] as $index => $item) {
                $product = Product::where('pro_code', $item['mov_pro_code'])->first();

                if ($product && $product->pro_und === 'und' && $item['mov_quantity'] != (int) $item['mov_quantity']) {
                    $validator->errors()->add(
                        "items.{$index}.mov_quantity",
                        "{$product->pro_name} se devuelve por unidades enteras."
                    );
                }

                $originalQty = $originalPurchase->movements()
                    ->where('mov_pro_code', $item['mov_pro_code'])
                    ->where('mov_war_id', $item['mov_war_id'])
                    ->sum('mov_quantity');

                $alreadyReturned = $returnedMovements
                    ->where('mov_pro_code', $item['mov_pro_code'])
                    ->where('mov_war_id', $item['mov_war_id'])
                    ->sum('mov_quantity');

                if (round($item['mov_quantity'], 4) > round($originalQty - $alreadyReturned, 4)) {
                    $validator->errors()->add(
                        "items.{$index}.mov_quantity",
                        "La cantidad de devolución para el producto {$item['mov_pro_code']} supera el máximo permitido."
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_number.unique' => 'El número de documento ya existe.',
            'invoice_reference.required' => 'Debe seleccionar la factura original.',
            'items.required' => 'Debe agregar al menos un producto.',
            'items.*.mov_quantity.required' => 'Debe ingresar una cantidad.',
            'items.*.mov_pro_code.required' => 'Debe seleccionar un producto.',
            'items.*.mov_war_id.required' => 'Debe seleccionar un almacén.',
        ];
    }
}
