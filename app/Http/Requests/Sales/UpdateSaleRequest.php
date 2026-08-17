<?php

namespace App\Http\Requests\Sales;

use App\Models\Product;
use App\Models\Rate;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Services\Finance\RateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'sls_number' => ['required', 'string', 'max:50', Rule::unique('sale_orders', 'sls_number')->ignore($this->route('sale'))],
            'sls_date' => 'required|date',
            'sls_customer_id' => 'required|exists:customers,id',
            'sls_valorcam' => 'required|numeric',
            'sls_coin' => 'required|string',
            'sls_subtotal' => 'required|numeric',
            'sls_exento' => 'required|numeric',
            'sls_iva' => 'required|numeric',
            'sls_total' => 'required|numeric',
            'sls_payment_condition' => ['required', 'string', 'in:cash,credit'],
            'sls_credit_days' => ['required', 'integer', 'min:0', 'max:365'],
            'sls_due_date' => ['required', 'date', 'after_or_equal:sls_date'],

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:movements_sales,id',
            'items.*.mvs_pro_code' => 'required|exists:products,pro_code|distinct',
            'items.*.mvs_war_id' => 'required|exists:warehouses,id',
            'items.*.mvs_quantity' => 'required|numeric|min:0.01',
            'items.*.mvs_unit_price' => 'required|numeric|min:0',
            'items.*.mvs_unit_price_ref' => 'required|numeric|min:0',
            'items.*.mvs_pro_iva' => 'required|numeric|min:0',
        ];

        if ($this->requiresPayment()) {
            $rules['payment'] = ['required', 'array'];
            $rules['payment.payment_date'] = ['required', 'date'];
            $rules['payment.payments'] = ['required', 'array', 'min:1'];
            $rules['payment.vueltos'] = ['nullable', 'array'];
            $rules['payment.vueltos.*.vuelto_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['payment.vueltos.*.vuelto_currency'] = ['nullable', 'string'];
            $rules['payment.vueltos.*.vuelto_bank_account_id'] = ['nullable', 'exists:bank_accounts,id'];
            $rules['payment.payments.*.payment_currency'] = ['required', 'string'];
            $rules['payment.payments.*.payment_rate'] = ['required', 'numeric', 'gt:0'];
            $rules['payment.payments.*.bank_account_id'] = ['required', 'exists:bank_accounts,id'];
            $rules['payment.payments.*.payment_method'] = ['required', 'string'];
            $rules['payment.payments.*.payment_reference'] = ['required', 'string', 'max:50'];
            $rules['payment.payments.*.payment_description'] = ['nullable', 'string', 'max:150'];
            $rules['payment.payments.*.payment_amount'] = ['required', 'numeric', 'gt:0'];
        }

        return $rules;
    }

    private function requiresPayment(): bool
    {
        if ($this->input('sls_payment_condition') !== 'cash') {
            return false;
        }

        $receivable = SaleOrder::find($this->route('sale'))?->accountsReceivable;

        return ! $receivable || (float) $receivable->acr_balance > 0;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validatePaymentCoversTotal($validator);
            $this->validateItems($validator);
        });
    }

    private function validatePaymentCoversTotal($validator): void
    {
        if (! $this->requiresPayment()) {
            return;
        }

        $total = (float) $this->input('sls_total');
        $docCurrency = $this->input('sls_coin');
        $payments = $this->input('payment.payments', []);

        if (empty($payments) || ! $docCurrency) {
            return;
        }

        $rate = app(RateService::class)->getBestRateForDate($this->input('payment.payment_date'))
            ?? Rate::orderBy('rate_date', 'desc')->first();

        if (! $rate) {
            return;
        }

        $totalPaidInDoc = 0;

        foreach ($payments as $payment) {
            $totalPaidInDoc += Rate::convert(
                (float) $payment['payment_amount'],
                $payment['payment_currency'],
                $docCurrency,
                $rate,
            );
        }

        foreach ($this->input('payment.vueltos', []) as $vuelto) {
            $amount = (float) ($vuelto['vuelto_amount'] ?? 0);

            if (
                $amount <= 0
                || empty($vuelto['vuelto_currency'])
                || empty($vuelto['vuelto_bank_account_id'])
            ) {
                continue;
            }

            $totalPaidInDoc -= Rate::convert(
                $amount,
                $vuelto['vuelto_currency'],
                $docCurrency,
                $rate,
            );
        }

        if ($totalPaidInDoc + 0.005 < $total) {
            $validator->errors()->add(
                'payment.payments',
                'El monto debe cubrir el total de la factura ('.number_format($total, 4)." {$docCurrency})."
            );
        }
    }

    private function validateItems($validator): void
    {
        $items = $this->input('items', []);

        foreach ($items as $index => $item) {
            $product = Product::where('pro_code', $item['mvs_pro_code'])->first();
            if (! $product) {
                continue;
            }

            $esCompuesto = $product->pro_type?->value === 'compuesto';
            $latestPrice = $product->latestPrice();
            $latestCost = $product->latestCost();

            if (! $latestPrice) {
                $validator->errors()->add(
                    "items.{$index}.mvs_unit_price_ref",
                    "El producto {$product->pro_name} no tiene un precio registrado."
                );

                continue;
            }

            $isExistingItem = isset($item['id']) && ! empty($item['id']);

            if (! $isExistingItem && (float) $item['mvs_unit_price_ref'] !== (float) $latestPrice->price_usd) {
                $validator->errors()->add(
                    "items.{$index}.mvs_unit_price_ref",
                    "El precio del producto {$product->pro_name} no es válido."
                );
            }

            if (! $esCompuesto && $latestCost && (float) $latestPrice->price_usd <= (float) $latestCost->cost_pro_cost_ref) {
                $validator->errors()->add(
                    "items.{$index}.mvs_unit_price_ref",
                    "El precio del producto {$product->pro_name} debe ser mayor al costo."
                );
            }

            if ($product->pro_und->value === 'und' && $item['mvs_quantity'] != (int) $item['mvs_quantity']) {
                $validator->errors()->add(
                    "items.{$index}.mvs_quantity",
                    "{$product->pro_name} se vende por unidades. Ingrese un número entero."
                );
            }

            if (! $esCompuesto) {
                $stock = Stock::where('stk_pro_code', $item['mvs_pro_code'])
                    ->where('stk_war_id', 1)
                    ->value('stk_cant') ?? 0;

                if ((float) $item['mvs_quantity'] > (float) $stock) {
                    $validator->errors()->add(
                        "items.{$index}.mvs_quantity",
                        "Stock insuficiente de {$product->pro_name}. Disponible: {$stock}"
                    );
                }
            }
        }
    }
}
