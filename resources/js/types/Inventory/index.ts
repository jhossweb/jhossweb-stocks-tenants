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

export interface AdjustmentReason {
    id: number;
    adjr_code: string;
    adjr_name: string;
    adjr_description: string | null;
}

export interface AdjustmentProductStock {
    war_id: number;
    cant: number;
}

export interface AdjustmentProduct {
    pro_code: string;
    pro_name: string;
    pro_und: string | null;
    pro_iva: string | null;
    pro_type: string | null;
    stocks: AdjustmentProductStock[];
    total_stock: number;
}

export interface InventoryAdjustmentItem {
    id?: number;
    min_pro_code: string;
    min_war_id: number;
    min_quantity: number;
    min_reason_id: string;
}

export interface InventoryAdjustmentMovement {
    id: number;
    min_pro_code: string;
    min_war_id: number;
    min_quantity: number;
    min_reason_id: number;
    product?: AdjustmentProduct | null;
    warehouse?: Warehouse | null;
    reason?: AdjustmentReason | null;
}

export interface InventoryAdjustment {
    id: number;
    inv_number: string;
    inv_date: string;
    inv_notes: string | null;
    inv_status: string;
    created_at: string;
    movements: InventoryAdjustmentMovement[];
}

export interface InventoryAdjustmentPaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface InventoryAdjustmentPage {
    data: InventoryAdjustment[];
    links: InventoryAdjustmentPaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
