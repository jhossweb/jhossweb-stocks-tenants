## Objective
Improve Cash Closing (cuadre de caja) breakdown UX and refactor cobranza module into clean architecture layers (DTO, FormRequest, Service, Repository).

## Important Details
- CHEQUE → PAGO_MOVIL: only TransactionMethod enum + factory changed; all forms & validations auto-update since they iterate `TransactionMethod::values()`
- `cc_total_received` now sums ALL receipts (removed `btr_method = 'EFECTIVO'` filter)
- Breakdown hierarchy: coin → method → items; columns: Comprobante, Fecha, Nro. Documento, Cliente, Banco, Monto
- Date format in detail table: DD-MM-YYYY using `Intl.DateTimeFormat('es-ES')`

## Work State
### Completed
- TransactionMethod enum: `CHECK = 'CHEQUE'` → `MOBILE_PAYMENT = 'PAGO_MOVIL'`
- BankTransactionFactory: `'CHEQUE'` → `'PAGO_MOVIL'`
- ReceiptController refactored into ReceiptService, ReceiptRepository, StoreReceiptRequest, UpdateReceiptRequest, ReceiptPaymentDTO, StoreReceiptDTO
- CashClosingRepository: new getReceivedByMethod() + getReceivedDetails() (per-document query)
- CashClosingService::calculatePreview() includes receipts_by_method + receipts_details
- CashClosingService: new computeBreakdown() for show view
- CashClosingController::show() passes breakdown to frontend
- Types: CashClosingPreview updated with receipts_details, groupedData (3-level hierarchy)
- Create.vue: hierarchical breakdown table, auto-load opening balance, warning when opening=0, 2-table layout (summary + detail)
- Show.vue: hierarchical breakdown table matching Create.vue, simplified hasBreakdown, removed ReceiptsByMethod import
- Lint + typecheck pass (no new errors)

### Active
- (none)

### Blocked
- (none)
