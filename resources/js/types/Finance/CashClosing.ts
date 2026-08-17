export type CashClosingStatus = 'balanced' | 'discrepancy'

export interface ReceiptsByMethod {
    EFECTIVO?: number
    TRANSFERENCIA?: number
    PAGO_MOVIL?: number
    OTRO?: number
    [key: string]: number | undefined
}

export interface ReceiptDetail {
    coin: string
    amount: number
    method: string
    reference: string
    payment_date: string
    document: string
    customer: string
    bank: string
}

export interface CountedDetail {
    coin: string
    method: string
    counted: number
}

export interface GroupedReceipts {
    [coin: string]: {
        [method: string]: number
    }
}

export interface CashClosingPreview {
    cc_date: string
    cc_opening_balance: number
    cc_total_sales: number
    cc_total_received: number
    cc_total_manual_inflows: number
    cc_total_manual_outflows: number
    cc_expected_closing: number | null
    cc_counted_amount: number | null
    cc_difference: number | null
    cc_status: CashClosingStatus
    receipts_by_method: ReceiptsByMethod
    receipts_details: ReceiptDetail[]
    grouped_receipts: GroupedReceipts
    vuelto_total: number
    vuelto_details: ReceiptDetail[]
    grouped_vueltos: GroupedReceipts
}

export interface CashMovementData {
    cm_type: 'inflow' | 'outflow'
    cm_amount: number
    cm_description: string
    cm_reference: string | null
}

export interface CashMovementRecord {
    id: number
    cm_cash_closing_id: number
    cm_type: 'inflow' | 'outflow'
    cm_amount: number
    cm_description: string
    cm_reference: string | null
}

export interface CashClosingRecord {
    id: number
    cc_date: string
    cc_opening_balance: number
    cc_total_sales: number
    cc_total_received: number
    cc_total_manual_inflows: number
    cc_total_manual_outflows: number
    cc_expected_closing: number | null
    cc_counted_amount: number | null
    cc_difference: number | null
    cc_status: CashClosingStatus
    cc_notes: string | null
    cc_closed_by: number
    cc_closed_at: string
    closed_by: { id: number; name: string } | null
    movements: CashMovementRecord[]
    cc_counted_details: CountedDetail[] | null
    cc_differences: Record<string, number> | null
}

export interface LastClosing {
    cc_date: string
    cc_counted_amount: number
}
