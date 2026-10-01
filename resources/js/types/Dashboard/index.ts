export interface CurrencyMap {
    USD?: number;
    COP?: number;
    BS?: number;
    EUR?: number;
}

export interface TrendData {
    direction: 'up' | 'down' | 'flat';
    percentage: number;
    /**
     * false cuando no hay tasa cargada y el porcentaje se calculó sobre una
     * suma de monedas heterogéneas. La UI debe advertirlo en vez de
     * presentarlo como una variación real.
     */
    reliable?: boolean;
}

export type TrendByCurrency = Record<string, TrendData>;

export type MonthlyNetSeries = Record<string, CurrencyMap>;

/**
 * Equivalente en dólares de un total multi-moneda, convertible con la tasa
 * más reciente registrada. `usd: null` significa que no hay ninguna tasa
 * cargada, no que el total sea cero.
 */
export interface UsdData {
    usd: number | null;
    rate_date: string | null;
    has_rate: boolean;
}

export interface ExpenseKpis extends UsdData {
    total: CurrencyMap;
    pending: CurrencyMap;
    trend: TrendData;
}

export interface CashFlowKpis {
    inflow: CurrencyMap;
    outflow: CurrencyMap;
    net: CurrencyMap;
    usd: {
        inflow: number | null;
        outflow: number | null;
        net: number | null;
    };
    trend: TrendByCurrency;
}

export interface ApArKpis {
    balance: CurrencyMap;
    balance_usd: CurrencyMap;
    collected?: CurrencyMap;
    paid?: CurrencyMap;
}

export interface ProfitLossItem extends UsdData {
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
    expensesFijo: UsdData & { total: CurrencyMap; trend: TrendData };
    expensesVariable: UsdData & { total: CurrencyMap; trend: TrendData };
    cashFlow: CashFlowKpis;
    receivables: ApArKpis;
    payables: ApArKpis;
    purchases: UsdData & {
        total: CurrencyMap;
        trend: TrendData;
        supplierCount: number;
        pending: CurrencyMap;
    };
}

export interface SaleOrderRow {
    id: number;
    sls_number: string;
    sls_date: string;
    sls_coin: string;
    sls_total: number;
    sls_status: string;
    sls_payment_condition: string;
    customer?: { clt_name: string } | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface SalesPage {
    data: SaleOrderRow[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface PurchaseOrderRow {
    id: number;
    invoice_number: string;
    invoice_date: string;
    invoice_supplier_id: number;
    invoice_coin: string;
    invoice_total: number;
    invoice_status: string;
    invoice_payment_condition: string;
    supplier?: { supplier_name: string } | null;
}

export interface PurchasesPage {
    data: PurchaseOrderRow[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
}

export interface CategoryAggregate extends UsdData {
    total: CurrencyMap;
    trend?: TrendData;
}

/** Una categoría de gasto con su participación en el total del período. */
export interface ExpenseCategoryRow {
    category_id: number;
    exc_name: string;
    exc_type: string;
    amounts: CurrencyMap;
    usd: number | null;
    share_pct: number;
}

export interface ExpenseBreakdown extends UsdData {
    total_by_currency: CurrencyMap;
    categories: ExpenseCategoryRow[];
}

export interface CategoryAggregates {
    ventas: CategoryAggregate;
    gastos: CategoryAggregate;
    finanzas: CategoryAggregate;
    compras: CategoryAggregate;
    inventario: CategoryAggregate;
}

export interface WarehouseStock {
    warehouse_id: number;
    war_name: string;
    product_count: number;
    total_quantity: number;
}

export interface StockDetailRow {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    war_id: number;
    war_name: string;
    quantity: number;
}

export interface InventoryValueItem {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    total_quantity: number;
    unit_cost_ref: number;
    total_value_ref: number;
}

export interface InventoryKpis {
    stockByWarehouse: WarehouseStock[];
    inventoryValue: CurrencyMap;
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

export interface AnalyticsSummary {
    expenses?: ExpenseKpis;
    cashFlow?: CashFlowKpis;
    receivables?: ApArKpis;
    payables?: ApArKpis;
}
