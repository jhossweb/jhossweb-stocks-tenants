export interface ReceiptBankTransaction {
    id: number;
    btr_bank_account_id: number | null;
    btr_type: string;
    btr_amount: string;
    btr_reference: string | null;
    btr_description: string | null;
    btr_valorcam: string;
    btr_diferencial: string;
    btr_coin: string;
    btr_method: string | null;
    btr_transaction_date: string | null;
    bank_account?: {
        id: number;
        bank_name: string;
        bank_currency: string;
    } | null;
}

export interface ReceiptCustomer {
    id: number;
    clt_name: string;
    clt_cedula: string | null;
    clt_address?: string | null;
    clt_phone?: string | null;
}

export interface ReceiptSaleOrder {
    id: number;
    sls_number: string;
    sls_coin: string;
    sls_total: string;
    sls_date: string;
    customer?: ReceiptCustomer | null;
}

export interface ReceiptAccountsReceivable {
    id: number;
    acr_sls_number: string;
    acr_total: string;
    acr_balance: string;
    acr_status: string;
    sale_order?: ReceiptSaleOrder | null;
}

export interface ReceiptHistoryEntry {
    id: number;
    rec_account_receivable_id: number;
    rec_bank_transaction_id: number | null;
    rec_amount: string;
    rec_diferencial: string;
    rec_type: string;
    created_at: string;
    accounts_receivable?: ReceiptAccountsReceivable | null;
    bank_transaction?: ReceiptBankTransaction | null;
}

export interface ReceiptHistoryGroup {
    id: number;
    group_key: number;
    rec_account_receivable_id: number;
    rec_type: string;
    created_at: string | null;
    total_usd: number;
    movements_count: number;
    banks: string[];
    references: string[];
    accounts_receivable?: ReceiptAccountsReceivable | null;
    receipts?: ReceiptHistoryEntry[];
}
