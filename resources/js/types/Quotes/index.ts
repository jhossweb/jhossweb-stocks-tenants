export interface Quote {
    id: number;
    sls_number: string;
    sls_date: string;
    sls_customer_id: number;
    sls_valorcam: string;
    sls_coin: string;
    sls_subtotal: string;
    sls_exento: string;
    sls_iva: string;
    sls_total: string;
    sls_quote_status: QuoteStatusValue;
    customer?: { id: number; clt_name?: string };
    created_at: string;
}

export type QuoteStatusValue =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'converted';

export const QUOTE_STATUS_LABEL: Record<QuoteStatusValue, string> = {
    pending: 'Pendiente',
    approved: 'Aprobada',
    rejected: 'Rechazada',
    converted: 'Convertida',
};

export interface QuoteItemForm {
    id?: number;
    mvs_pro_code: string;
    mvs_war_id: string | number;
    mvs_quantity: number;
    mvs_unit_price: number;
    mvs_unit_price_ref: number;
    mvs_pro_iva: number;
    _pending?: boolean;
}

export interface QuoteProduct {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    pro_iva: string;
    latest_price: number;
    latest_utility: number;
    total_stock: number;
    [key: string]: unknown;
}

export interface QuoteCustomer {
    id: number;
    clt_name?: string;
    [key: string]: unknown;
}

export interface QuoteWarehouse {
    id: number;
    war_name?: string;
    [key: string]: unknown;
}

export interface QuoteRate {
    rate_date: string;
    rate_usd: number | string;
    rate_cop: number | string;
    rate_eur: number | string;
    [key: string]: unknown;
}

export interface QuoteStockMissingItem {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    warehouse_id: number;
    warehouse_name: string;
    required: number;
    available: number;
    shortage: number;
    via_parent: string[];
}

export interface QuoteAvailability {
    ok: boolean;
    missing: QuoteStockMissingItem[];
}
