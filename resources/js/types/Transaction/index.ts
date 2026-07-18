export interface TransactionItem {
    mov_pro_code: string;
    mov_war_id: string | number;
    mov_quantity: number;
    mov_unit_cost_ref: number;
    mov_unit_cost: number;
    mov_pro_iva: number;
    id?: number;
    _pending?: boolean;
}

export interface TransactionTotals {
    subtotal: number;
    iva: number;
    exento: number;
    total: number;
}

export interface Product {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    pro_iva: string;
    pro_conversion_factor?: number;
    [key: string]: unknown;
}

export interface Supplier {
    id: number;
    supplier_name?: string;
    name?: string;
    supplier_rif?: string;
    [key: string]: unknown;
}

export interface Rate {
    rate_date: string;
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
    [key: string]: unknown;
}

export interface LatestRate {
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
    rate_cop_bs?: number;
    rate_date?: string;
}

export interface Warehouse {
    id: number;
    war_name?: string;
    [key: string]: unknown;
}

export interface ColumnConfig {
    key: string;
    label: string;
    class?: string;
    align?: 'left' | 'center' | 'right';
    width?: string;
}
