export type TenantStatus = 'active' | 'suspended';

export type TenantPlan = 'standard' | 'premium';

export interface CompanyCreateProps {
    domainSuffix: string;
}

export interface TenantRow {
    id: string;
    name: string;
    plan: TenantPlan | null;
    status: TenantStatus;
    domain: string | null;
    created_at: string | null;
}

export interface TenantsIndexProps {
    tenants: TenantRow[];
}

export interface TenantCreateProps {
    domainSuffix: string;
}

export interface TenantEditProps {
    tenant: TenantRow;
}
