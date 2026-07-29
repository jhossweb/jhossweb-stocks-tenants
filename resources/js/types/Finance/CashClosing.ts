export type CashClosingStatus = 'balanced' | 'discrepancy'

export interface CashClosingPreview {
    cc_date: string
    cc_opening_balance: number
    cc_total_sales: number
    cc_total_received: number
    cc_total_manual_inflows: number
    cc_total_manual_outflows: number
    cc_expected_closing: number
    cc_counted_amount: number
    cc_difference: number
    cc_status: CashClosingStatus
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
    cc_expected_closing: number
    cc_counted_amount: number
    cc_difference: number
    cc_status: CashClosingStatus
    cc_notes: string | null
    cc_closed_by: number
    cc_closed_at: string
    closed_by: { id: number; name: string } | null
    movements: CashMovementRecord[]
}

export interface LastClosing {
    cc_date: string
    cc_counted_amount: number
}
