export interface AccountPaymentBankTransaction {
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

export interface AccountPaymentEntry {
    id: number;
    apy_account_payable_id: number;
    apy_bank_transaction_id: number | null;
    apy_amount: string;
    apy_diferencial: string;
    apy_type: string;
    apy_invoice_reference: string | null;
    created_at: string;
    account_payable?: {
        id: number;
        acp_purchase_number: string;
        purchase_order?: {
            id: number;
            invoice_number: string;
            invoice_total: string;
            invoice_coin: string;
            invoice_date: string;
            supplier?: {
                id: number;
                supplier_name: string;
            } | null;
        } | null;
    } | null;
    bank_transaction?: AccountPaymentBankTransaction | null;
}
