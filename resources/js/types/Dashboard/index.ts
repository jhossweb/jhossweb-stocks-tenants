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

// Detail Types
export interface ExpenseCategoryItem {
    exc_name: string;
    exc_type: string;
    exp_coin: string;
    total: number;
}

export interface BankAccountItem {
    bank_name: string;
    bank_currency: string;
    btr_coin: string;
    total: number;
}

export interface NetAccountItem {
    bank_name: string;
    bank_currency: string;
    total: number;
}

export interface CustomerBalanceItem {
    customer_name: string;
    sls_coin: string;
    balance: number;
    balance_usd: number;
}

export interface SupplierBalanceItem {
    supplier_name: string;
    invoice_coin: string;
    balance: number;
    balance_usd: number;
}

// Detail response types per analytics page
export interface ExpenseDetails {
    byCategory: ExpenseCategoryItem[];
}

export interface CashFlowDetails {
    inflowByAccount: BankAccountItem[];
    outflowByAccount: BankAccountItem[];
    netByAccount: NetAccountItem[];
}

export interface ReceivableDetails {
    byCustomer: CustomerBalanceItem[];
}

export interface PayableDetails {
    bySupplier: SupplierBalanceItem[];
}

export interface ProductDetails {
    topSelling: ProductMovement[];
    worstSelling: ProductMovement[];
    lowStock: LowStockItem[];
}

export interface AnalyticsSummary {
    expenses?: ExpenseKpis;
    cashFlow?: CashFlowKpis;
    receivables?: ApArKpis;
    payables?: ApArKpis;
    products?: Pick<ProductKpis, 'totalSkus' | 'lowStock'>;
}
