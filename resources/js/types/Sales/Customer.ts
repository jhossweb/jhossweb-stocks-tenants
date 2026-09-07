export interface Customer {
    id: number;
    clt_name: string;
    clt_code?: string | null;
    clt_cedula?: string | null;
    clt_address?: string | null;
    clt_phone?: string | null;
    clt_email?: string | null;
    clt_website?: string | null;
    clt_credit_days?: number | null;
}
