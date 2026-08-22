import type { AgingSummary, ReceivableDocumentDetail } from '../Sales';

export interface SupplierBalanceSummary {
    supplier: {
        id: number | null;
        supplier_name: string;
    };
    coin: string;
    documents_count: number;
    total_balance: number;
    total_paid: number;
    aging: AgingSummary;
    oldest_due_date: string | null;
    details: ReceivableDocumentDetail[];
}
