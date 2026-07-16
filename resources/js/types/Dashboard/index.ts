export interface CurrencyMap {
    USD?: number;
    COP?: number;
    BS?: number;
    EUR?: number;
}

export interface TrendData {
    direction: 'up' | 'down' | 'flat';
    percentage: number;
}

export interface ExpenseKpis {
    total: CurrencyMap;
    pending: CurrencyMap;
    trend: TrendData;
}

export interface CashFlowKpis {
    inflow: CurrencyMap;
    outflow: CurrencyMap;
    net: CurrencyMap;
    trend: TrendData;
}

export interface ApArKpis {
    balance: CurrencyMap;
    balance_usd: CurrencyMap;
    collected?: CurrencyMap;
    paid?: CurrencyMap;
}

export interface LowStockItem {
    pro_name: string;
    stk_cant: number;
    pro_cant_min: number;
}

export interface ProductMovement {
    pro_name: string;
    quantity: number;
}

export interface ProductKpis {
    totalSkus: number;
    lowStock: LowStockItem[];
    topSelling: ProductMovement[];
    worstSelling: ProductMovement[];
}

export interface DashboardKpis {
    expenses: ExpenseKpis;
    cashFlow: CashFlowKpis;
    receivables: ApArKpis;
    payables: ApArKpis;
    products: ProductKpis;
}

export type KpiCardType = 'expenses' | 'inflow' | 'outflow' | 'net' | 'receivables' | 'payables';
