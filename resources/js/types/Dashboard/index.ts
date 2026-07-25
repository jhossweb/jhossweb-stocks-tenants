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

export interface ProfitLossItem {
    total: CurrencyMap;
    trend: TrendData;
}

export interface ProfitLossKpis {
    revenue: ProfitLossItem;
    cogs: ProfitLossItem;
    grossProfit: ProfitLossItem;
    expenses: ProfitLossItem;
    netProfit: ProfitLossItem;
}

export interface DashboardKpis {
    profitLoss: ProfitLossKpis;
    expensesFijo: { total: CurrencyMap; trend: TrendData };
    expensesVariable: { total: CurrencyMap; trend: TrendData };
    cashFlow: CashFlowKpis;
    receivables: ApArKpis;
    payables: ApArKpis;
    products: ProductKpis;
    purchases: { total: CurrencyMap; trend: TrendData; supplierCount: number; pending: CurrencyMap };
}

export interface CategoryAggregate {
    total: CurrencyMap;
    trend?: TrendData;
}

export interface CategoryAggregates {
    ventas: CategoryAggregate;
    gastos: CategoryAggregate;
    finanzas: CategoryAggregate;
    compras: CategoryAggregate;
}

export interface PurchaseItem {
    invoice_number: string;
    invoice_date: string;
    supplier_name: string;
    invoice_coin: string;
    invoice_total: number;
    invoice_status: string;
}

export interface SupplierPurchaseItem {
    supplier_name: string;
    invoice_coin: string;
    total: number;
}

export interface MonthPurchaseItem {
    month: string;
    invoice_coin: string;
    total: number;
}

export interface StatusPurchaseItem {
    invoice_status: string;
    invoice_coin: string;
    total: number;
}

export interface PurchaseDetails {
    purchases?: PurchaseItem[];
    bySupplier?: SupplierPurchaseItem[];
    byMonth?: MonthPurchaseItem[];
    byStatus?: StatusPurchaseItem[];
}

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
