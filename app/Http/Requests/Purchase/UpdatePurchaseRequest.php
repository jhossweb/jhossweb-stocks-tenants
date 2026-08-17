<?php

namespace App\Http\Requests\Purchase;

use App\Models\PurchaseOrder;
use App\Models\Rate;
use App\Services\Finance\RateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'invoice_number' => ['required', 'string', 'max:50', Rule::unique('purchase_orders', 'invoice_number')->ignore($this->route('purchase'))],
            'invoice_date' => 'required|date',
            'invoice_supplier_id' => 'required|exists:suppliers,id',
            'invoice_valorcam' => 'required|numeric',
            'invoice_coin' => 'required|string',
            'invoice_subtotal' => 'required|numeric',
            'invoice_exento' => 'required|numeric',
            'invoice_iva' => 'required|numeric',
            'invoice_total' => 'required|numeric',
            'invoice_payment_condition' => ['required', 'string', 'in:cash,credit'],
            'invoice_credit_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_due_date' => ['required', 'date', 'after_or_equal:invoice_date'],

            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:movements_purchases,id',
            'items.*.mov_pro_code' => 'required|exists:products,pro_code|distinct',
            'items.*.mov_war_id' => 'required|exists:warehouses,id',
            'items.*.mov_quantity' => 'required|numeric|min:0.01',
            'items.*.mov_unit_cost' => 'required|numeric|min:0',
            'items.*.mov_unit_cost_ref' => 'required|numeric|min:0',
            'items.*.mov_pro_iva' => 'required|numeric|min:0',
        ];

        if ($this->requiresPayment()) {
            $rules['payment'] = ['required', 'array'];
            $rules['payment.payment_date'] = ['required', 'date'];
            $rules['payment.payments'] = ['required', 'array', 'min:1'];
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
        if ($this->input('invoice_payment_condition') !== 'cash') {
            return false;
        }

        $payable = PurchaseOrder::find($this->route('purchase'))?->accountsPayable;

        return ! $payable || (float) $payable->acp_balance > 0;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validatePaymentCoversTotal($validator);
        });
    }

    private function validatePaymentCoversTotal($validator): void
    {
        if (! $this->requiresPayment()) {
            return;
        }

        $total = (float) $this->input('invoice_total');
        $docCurrency = $this->input('invoice_coin');
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

        if ($totalPaidInDoc + 0.005 < $total) {
            $validator->errors()->add(
                'payment.payments',
                'El monto debe cubrir el total de la factura ('.number_format($total, 4)." {$docCurrency})."
            );
        }

        if ($totalPaidInDoc - 0.005 > $total) {
            $validator->errors()->add(
                'payment.payments',
                'El pago no puede exceder el total de la factura ('.number_format($total, 4)." {$docCurrency})."
            );
        }
    }
}
