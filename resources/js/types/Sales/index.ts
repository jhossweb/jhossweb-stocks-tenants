export interface AgingSummary {
    current: number;
    days_1_30: number;
    days_31_60: number;
    days_61_90: number;
    days_over_90: number;
}

export interface ReceivableDocumentDetail {
    id: number;
    number: string;
    date: string | null;
    due_date: string | null;
    days_remaining: number;
    total: number;
    paid: number;
    balance: number;
}

export interface CustomerBalanceSummary {
    customer: {
        id: number | null;
        clt_code: string | null;
        clt_name: string;
    };
    coin: string;
    documents_count: number;
    total_balance: number;
    total_paid: number;
    aging: AgingSummary;
    oldest_due_date: string | null;
    details: ReceivableDocumentDetail[];
}
