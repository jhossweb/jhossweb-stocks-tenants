export interface Company {
    id?: number;
    cmp_name: string;
    cmp_rif: string;
    cmp_address?: string | null;
    cmp_phone?: string | null;
    cmp_email?: string | null;
    cmp_website?: string | null;
    cmp_logo?: string | null;
}

export interface TenantUserRole {
    id: number;
    name: string;
}

export interface TenantUser {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    deactivated_at: string | null;
    roles: TenantUserRole[];
    created_at: string | null;
}

export interface RolePermission {
    id: number;
    name: string;
}

export interface TenantRole {
    id: number;
    name: string;
    users_count: number;
    permissions: RolePermission[];
}

export interface PermissionItem {
    key: string;
    label: string;
}

export interface PermissionGroup {
    key: string;
    label: string;
    permissions: PermissionItem[];
}

export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
}
