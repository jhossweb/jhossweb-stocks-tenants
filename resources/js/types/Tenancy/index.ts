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

export interface TenantDetail {
    id: string;
    name: string;
    plan: TenantPlan | null;
    status: TenantStatus;
    domain: string | null;
    admin_email: string | null;
    admin_name: string | null;
    rif: string | null;
    address: string | null;
    phone: string | null;
    trial_ends_at: string | null;
    internal_notes: string | null;
    status_source: string | null;
    suspended_reason: string | null;
    suspended_at: string | null;
    suspended_by: string | null;
    schema_version: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface PlanSummary {
    id: number;
    name: string;
    billing_cycle: string;
    price_usd: string;
}

export interface LimitGroup {
    key: string;
    label: string;
    limits: { key: string; label: string; scope: string }[];
}

export interface TenantShowProps {
    tenant: TenantDetail;
    plans: PlanSummary[];
    limit_groups: LimitGroup[];
    limit_overrides: Record<string, number>;
    plan_limits: Record<string, number | null>;
}

export type SubscriptionStatus =
    | 'trialing'
    | 'active'
    | 'past_due'
    | 'suspended'
    | 'canceled'
    | 'expired';

export type TenantEventType =
    | 'tenant_created'
    | 'tenant_updated'
    | 'plan_assigned'
    | 'plan_changed'
    | 'tenant_activated'
    | 'tenant_suspended'
    | 'tenant_canceled'
    | 'payment_registered'
    | 'payment_voided'
    | 'password_reset'
    | 'status_changed';

export type CurrencyCode = 'USD' | 'COP' | 'BS' | 'VES' | 'EUR';

export type PaymentMethod =
    | 'transfer'
    | 'mobile_payment'
    | 'cash'
    | 'card'
    | 'other';

export type PaymentStatus = 'pending' | 'paid' | 'voided';

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface TenantOption {
    id: string;
    name: string;
}

export interface PlanOption {
    id: number;
    code: string;
    name: string;
    billing_cycle: string;
    price_usd: string;
    trial_days: number;
}

export interface SubscriptionOption {
    id: number;
    label: string;
}

export interface SubscriptionRow {
    id: number;
    tenant_id: string;
    tenant_name: string | null;
    plan_id: number | null;
    plan_name: string | null;
    status: SubscriptionStatus;
    started_at: string | null;
    trial_ends_at: string | null;
    current_period_start: string | null;
    current_period_end: string | null;
    canceled_at: string | null;
    grace_days: number;
    created_at: string | null;
}

export interface SubscriptionsIndexProps {
    subscriptions: Paginated<SubscriptionRow>;
    filters: { search?: string; status?: string; tenant_id?: string };
    statuses: SubscriptionStatus[];
}

export interface SubscriptionsFormProps {
    tenants: TenantOption[];
    plans: PlanOption[];
    selectedTenantId?: string | null;
}

export interface SubscriptionEditProps {
    subscription: SubscriptionRow;
    tenants: TenantOption[];
    plans: PlanOption[];
}

export interface BillingRateRow {
    id: number;
    rate_date: string;
    rate_usd: string;
    rate_cop: string;
    rate_eur: string;
    rate_cop_bs: string;
}

export interface RatesIndexProps {
    rates: Paginated<BillingRateRow>;
    filters: { search?: string };
}

export interface PaymentRow {
    id: number;
    receipt_number: string;
    subscription_id: number;
    tenant_id: string;
    tenant_name: string | null;
    plan_name: string | null;
    amount: string;
    currency: CurrencyCode;
    exchange_rate: string;
    amount_usd: string;
    method: PaymentMethod;
    reference: string | null;
    paid_at: string | null;
    status: PaymentStatus;
    recorded_by: string | null;
    voided_at: string | null;
    voided_by: string | null;
    is_voided: boolean;
    notes: string | null;
    created_at: string | null;
}

export interface PaymentsIndexProps {
    payments: Paginated<PaymentRow>;
    filters: { search?: string; status?: string; tenant_id?: string };
    totalPaidUsd: number;
}

export interface PaymentsCreateProps {
    subscriptions: SubscriptionOption[];
    latestRate: BillingRateRow | null;
    selectedSubscriptionId: string | null;
    currencies: CurrencyCode[];
    methods: PaymentMethod[];
}

export interface PaymentShowProps {
    payment: PaymentRow;
}
