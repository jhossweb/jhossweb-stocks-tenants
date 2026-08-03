export type PaymentEntity = 'sale' | 'purchase';

export interface PaymentPending {
    entity: PaymentEntity;
    acr_id?: number;
    acp_id?: number;
    document_number: string;
    amount: number;
    currency: string;
    date: string;
    valorcam?: number;
    customer_id?: number | string;
    supplier_id?: number | string;
}
