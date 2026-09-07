export interface Category {
    id: number;
    cat_name: string;
    products_count?: number;
}

export interface Brand {
    id: number;
    bra_name: string;
    products_count?: number;
}

export interface Warehouse {
    id: number;
    war_name: string;
    stocks_count?: number;
}

export interface ProductPriceRow {
    pro_code: string;
    pro_name: string;
    pro_und: string;
    category: string | null;
    brand: string | null;
    last_cost: number;
    last_price: number | null;
    last_utility: number | null;
    real_margin: number | null;
    variation_pct: number | null;
    last_price_date: string | null;
}

export interface PriceHistoryEntry {
    type: 'precio' | 'costo';
    date: string;
    amount: number;
    utility: number | null;
    user: string | null;
}

export interface PriceHistoryResponse {
    product: {
        pro_code: string;
        pro_name: string;
        pro_und: string;
        category: string | null;
        brand: string | null;
    };
    history: PriceHistoryEntry[];
}
