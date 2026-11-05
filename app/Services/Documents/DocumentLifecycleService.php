<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Enum\Finanzas\TypePayment;
use App\Enum\Finanzas\TypeReceipt;
use App\Models\AccountPayments;
use App\Models\BankTransaction;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Payroll;
use App\Models\PayrollPeriod;
use App\Models\PurchaseOrder;
use App\Models\Receipt;
use App\Models\SaleOrder;
use App\Models\User;
use App\Services\Settings\TenantAuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentLifecycleService
{
    public function __construct(
        private readonly TenantAuditLogger $audit,
        private readonly CashClosingGuard $cashClosing,
        private readonly StockReversalService $stockReversal,
    ) {}

    public function deactivate(Model $document, User $actor): void
    {
        $this->assertDeactivatable($document);

        if (! $document->isActiveDocument()) {
            return;
        }

        $document->forceFill(['is_active' => false])->save();

        $this->audit->log('document.deactivated', $document, [], [
            'is_active' => false,
            'actor_id' => $actor->id,
        ]);
    }

    public function activate(Model $document): void
    {
        if ($document->voided_at !== null) {
            throw new \DomainException('Un documento anulado no puede reactivarse.');
        }

        if ($document->isActiveDocument()) {
            return;
        }

        $document->forceFill(['is_active' => true])->save();

        $this->audit->log('document.activated', $document, [], [
            'is_active' => true,
        ]);
    }

    public function void(Model $document, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw new \DomainException('Debes indicar un motivo para anular el documento.');
        }

        if ($document->voided_at !== null) {
            throw new \DomainException('El documento ya está anulado.');
        }

        $voidability = $this->voidability($document);

        if (! $voidability['can_void']) {
            throw new \DomainException((string) $voidability['reason']);
        }

        DB::transaction(function () use ($document, $actor, $reason): void {
            if ($this->affectsStock($document)) {
                $this->stockReversal->reverse($document);
            }

            $this->reverseFinancialEffects($document);

            $document->forceFill([
                'is_active' => false,
                'voided_at' => now(),
                'voided_by' => $actor->id,
                'void_reason' => $reason,
            ])->save();

            $this->audit->log('document.voided', $document, [], [
                'void_reason' => $reason,
                'actor_id' => $actor->id,
            ]);
        });
    }

    /**
     * @return array{can_void: bool, reason: ?string}
     */
    public function voidability(Model $document): array
    {
        if ($document->voided_at !== null) {
            return ['can_void' => false, 'reason' => 'El documento ya está anulado.'];
        }

        try {
            $this->cashClosing->assertNotInClosedCashClosing($document);
            $this->assertVoidable($document);

            if ($this->affectsStock($document)) {
                $this->stockReversal->assertCanReverse($document);
            }

            return ['can_void' => true, 'reason' => null];
        } catch (\DomainException $e) {
            return ['can_void' => false, 'reason' => $e->getMessage()];
        }
    }

    private function assertDeactivatable(Model $document): void
    {
        if ($document instanceof SaleOrder) {
            if ($document->sls_trans_type_code !== 'COT') {
                throw new \DomainException('Las facturas y devoluciones solo se pueden anular, no inactivar.');
            }

            if ($document->sls_quote_status === 'converted') {
                throw new \DomainException('La cotización ya fue convertida a factura.');
            }

            return;
        }

        if ($document instanceof PurchaseOrder || $document instanceof Receipt || $document instanceof AccountPayments || $document instanceof Inventory) {
            throw new \DomainException('Este documento solo se puede anular, no inactivar.');
        }

        $this->assertPhaseOneDocument($document);
    }

    private function assertVoidable(Model $document): void
    {
        if ($document instanceof SaleOrder) {
            $this->assertSaleOrderVoidable($document);

            return;
        }

        if ($document instanceof PurchaseOrder) {
            $this->assertPurchaseOrderVoidable($document);

            return;
        }

        if ($document instanceof Receipt || $document instanceof AccountPayments) {
            $this->assertPaymentVoidable($document);

            return;
        }

        if ($document instanceof Inventory) {
            return;
        }

        $this->assertPhaseOneDocument($document);
    }

    private function assertSaleOrderVoidable(SaleOrder $sale): void
    {
        if ($sale->sls_trans_type_code === 'COT') {
            if ($sale->sls_quote_status === 'converted') {
                throw new \DomainException('La cotización ya fue convertida a factura.');
            }

            return;
        }

        if ($sale->sls_trans_type_code === 'FACV') {
            if ($sale->childrenReturns()->exists()) {
                throw new \DomainException('La factura tiene devoluciones asociadas. Anula primero las devoluciones.');
            }

            if ($sale->sls_payment_condition === 'cash') {
                throw new \DomainException('La factura es de contado y tiene pago; no se puede anular.');
            }

            if ($sale->sls_status === 'paid' || $sale->accountsReceivable?->payments()->exists()) {
                throw new \DomainException('La factura está pagada o tiene abonos registrados; no se puede anular.');
            }

            return;
        }

        if ($sale->sls_trans_type_code === 'DEVV') {
            return;
        }

        throw new \DomainException('Tipo de documento de venta no soportado para anulación.');
    }

    private function assertPurchaseOrderVoidable(PurchaseOrder $purchase): void
    {
        if ($purchase->invoice_trans_type_code === 'FAC') {
            throw new \DomainException('La anulación de facturas de compra no está disponible todavía porque requiere ajustar el historial de costos.');
        }

        if ($purchase->invoice_trans_type_code === 'DEVC') {
            return;
        }

        throw new \DomainException('Tipo de documento de compra no soportado para anulación.');
    }

    private function assertPaymentVoidable(Receipt|AccountPayments $payment): void
    {
        if ($payment instanceof Receipt && $payment->rec_type === TypeReceipt::RETURN_CREDIT->value) {
            throw new \DomainException('Este crédito pertenece a una devolución de venta. Anula la devolución asociada.');
        }

        if ($payment instanceof AccountPayments && $payment->apy_type === TypePayment::RETURN_CREDIT->value) {
            throw new \DomainException('Este crédito pertenece a una devolución de compra. Anula la devolución asociada.');
        }
    }

    private function assertPhaseOneDocument(Model $document): void
    {
        if ($document instanceof Expense) {
            if ($document->isPaid() || $document->hasPayments()) {
                throw new \DomainException('No puedes modificar un gasto con pagos registrados.');
            }

            return;
        }

        if ($document instanceof Payroll || $document instanceof PayrollPeriod) {
            if ($document->isPaid()) {
                throw new \DomainException('No puedes modificar una nómina/período pagado.');
            }

            return;
        }

        throw new \DomainException('Documento no soportado para anulación.');
    }

    private function affectsStock(Model $document): bool
    {
        return $document instanceof SaleOrder
            || $document instanceof PurchaseOrder
            || $document instanceof Inventory;
    }

    private function reverseFinancialEffects(Model $document): void
    {
        if ($document instanceof SaleOrder && $document->sls_trans_type_code === 'DEVV') {
            $this->reverseSaleReturnCredit($document);

            return;
        }

        if ($document instanceof PurchaseOrder && $document->invoice_trans_type_code === 'DEVC') {
            $this->reversePurchaseReturnCredit($document);

            return;
        }

        if ($document instanceof Receipt) {
            $this->reverseReceipt($document);

            return;
        }

        if ($document instanceof AccountPayments) {
            $this->reverseAccountPayment($document);
        }
    }

    private function reverseSaleReturnCredit(SaleOrder $return): void
    {
        if (! $return->sls_reference) {
            return;
        }

        $originalSale = SaleOrder::where('sls_number', $return->sls_reference)->first();
        $ar = $originalSale?->accountsReceivable;

        if (! $ar) {
            return;
        }

        $receipt = $ar->payments()
            ->where('rec_type', TypeReceipt::RETURN_CREDIT->value)
            ->first();

        if (! $receipt) {
            return;
        }

        $ar->reversePayment($receipt);
        $receipt->delete();
    }

    private function reversePurchaseReturnCredit(PurchaseOrder $return): void
    {
        if (! $return->invoice_reference) {
            return;
        }

        $originalPurchase = PurchaseOrder::where('invoice_number', $return->invoice_reference)->first();
        $ap = $originalPurchase?->accountsPayable;

        if (! $ap) {
            return;
        }

        $payment = $ap->payments()
            ->where('apy_type', TypePayment::RETURN_CREDIT->value)
            ->where('apy_invoice_reference', $return->invoice_number)
            ->first();

        if (! $payment) {
            return;
        }

        $ap->reversePayment($payment);
        $payment->delete();
    }

    private function reverseReceipt(Receipt $receipt): void
    {
        $ar = $receipt->accountsReceivable;

        if ($ar) {
            $ar->reversePayment($receipt);
        }

        $this->zeroBankTransaction(
            $receipt->bankTransaction,
            (float) $receipt->rec_amount,
        );
    }

    private function reverseAccountPayment(AccountPayments $payment): void
    {
        $ap = $payment->accountPayable;

        if ($ap) {
            $ap->reversePayment($payment);
        }

        $this->zeroBankTransaction(
            $payment->bankTransaction,
            (float) $payment->apy_amount,
        );
    }

    private function zeroBankTransaction(?BankTransaction $bankTransaction, float $amount): void
    {
        if ($bankTransaction === null) {
            return;
        }

        $coin = $bankTransaction->btr_coin?->value ?? $bankTransaction->btr_coin;
        $amountInPaymentCoin = is_string($coin) && strtoupper($coin) !== 'USD' && $bankTransaction->btr_valorcam > 0
            ? round($amount * (float) $bankTransaction->btr_valorcam, 4)
            : $amount;

        $bankTransaction->btr_amount = max(0, (float) $bankTransaction->btr_amount - $amountInPaymentCoin);
        $bankTransaction->btr_diferencial = 0;
        $bankTransaction->save();
    }
}
