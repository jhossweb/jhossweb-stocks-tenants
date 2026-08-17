export interface PaymentEntry {
    payment_currency: string;
    payment_rate: string | number;
    bank_account_id: string;
    payment_method: string;
    payment_reference: string;
    payment_description: string;
    payment_amount: number | string;
}

export interface VueltoEntry {
    vuelto_amount: number | string;
    vuelto_currency: string;
    vuelto_bank_account_id: string;
}

export interface PaymentSectionModel {
    payment_date: string;
    payments: PaymentEntry[];
    vueltos?: VueltoEntry[];
}
