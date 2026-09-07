export interface BankAccount {
    id: number;
    bank_name: string;
    bank_account_number: string;
    bank_type: string;
    bank_currency: string;
    bank_status: boolean;
    bank_description?: string | null;
    bank_balance?: string | number;
    formatted_balance?: string;
}
