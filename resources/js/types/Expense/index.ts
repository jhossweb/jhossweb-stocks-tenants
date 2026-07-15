export interface ExpenseCategory {
    id: number;
    exc_name: string;
    exc_type: 'fijo' | 'variable';
    exc_description: string | null;
    expenses_count?: number;
}

export interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
    formatted_balance?: string;
}

export interface BankTransaction {
    id: number;
    btr_amount: string;
    btr_coin: string;
    btr_valorcam: string;
    btr_reference: string;
    btr_description: string;
    btr_balance: string;
    btr_diferencial: string;
    btr_method: string;
    btr_transaction_date: string | null;
    created_at: string;
    bank_account: BankAccount;
}

export interface Expense {
    id: number;
    exp_category_id: number;
    exp_description: string;
    exp_amount: string;
    exp_paid_amount: string;
    exp_remaining_balance: string;
    exp_date: string;
    exp_status: 'pendiente' | 'parcial' | 'pagado';
    exp_coin: string;
    exp_valorcam: string;
    exp_payment_date: string | null;
    exp_bank_transaction_id: number | null;
    category?: ExpenseCategory;
    payments?: BankTransaction[];
    created_at: string;
    updated_at: string;
}

export interface LatestRate {
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
    rate_cop_bs?: number;
    rate_date: string;
}

export interface PaginatedData<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}
