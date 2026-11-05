<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    ShoppingCart,
    FileText,
    ArrowRightLeft,
    BarChart3,
    Users,
    Building2,
    PackageSearch,
    CircleDollarSign,
    Settings,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { useTenantFeatures } from '@/composables/Tenancy/useTenantFeatures';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    /* {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },*/
    {
        title: 'Inventory',
        href: '#',
        icon: PackageSearch,
        permission: 'inventory.products.view',
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Productos',
                        href: '/products',
                        permission: 'inventory.products.view',
                    },
                    {
                        title: 'Categorías',
                        href: '/categories',
                        permission: 'inventory.categories.view',
                    },
                    {
                        title: 'Marcas',
                        href: '/brands',
                        permission: 'inventory.brands.view',
                    },
                    {
                        title: 'Almacenes',
                        href: '/warehouses',
                        permission: 'inventory.warehouses.view',
                    },
                ],
            },
            {
                title: 'Análisis',
                href: '#',
                icon: BarChart3,
                items: [
                    {
                        title: 'Kardex',
                        href: '/products',
                        permission: 'inventory.kardex.view',
                    },
                    {
                        title: 'Lista de Precios',
                        href: '/inventory/list-prices',
                        permission: 'inventory.prices.view',
                    },
                ],
            },
            {
                title: 'Transacciones',
                href: '#',
                icon: ArrowRightLeft,
                items: [
                    {
                        title: 'Ajuste de Inventario',
                        href: '/inventory/adjustments',
                        permission: 'inventory.adjustments.view',
                    },
                    {
                        title: 'Ajustar Precios',
                        href: '/inventory/adjust-prices',
                        permission: 'inventory.prices.view',
                    },
                ],
            },
        ],
    },
    {
        title: 'Compras',
        href: '#',
        icon: ShoppingCart,
        permission: 'purchases.invoices.view',
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Proveedores',
                        href: '/suppliers',
                        permission: 'purchases.suppliers.view', // Assuming this path, might need to be adjusted
                    },
                ],
            },
            {
                title: 'Transacciones',
                href: '#',
                icon: ArrowRightLeft,
                items: [
                    {
                        title: 'Compras',
                        href: '/purchases',
                        permission: 'purchases.invoices.view',
                    },
                    {
                        title: 'Devoluciones',
                        href: '/purchase-returns',
                        permission: 'purchases.returns.view',
                    },
                ],
            },
            {
                title: 'Análisis',
                href: '#',
                icon: BarChart3,
                items: [
                    {
                        title: 'Cuentas por Pagar',
                        href: '/accounts-payable',
                        permission: 'purchases.payables.view',
                    },
                    {
                        title: 'CxP por Proveedor',
                        href: '/accounts-payable/by-supplier',
                        permission: 'purchases.payables.view',
                    },
                ],
            },
        ],
    },
    {
        title: 'Ventas',
        href: '#',
        icon: Users,
        permission: 'sales.invoices.view',
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Clientes',
                        href: '/customers',
                        permission: 'sales.customers.view',
                    },
                ],
            },
            {
                title: 'Transacciones',
                href: '#',
                icon: ArrowRightLeft,
                items: [
                    {
                        title: 'Factura de venta',
                        href: '/sales',
                        permission: 'sales.invoices.view',
                    },
                    {
                        title: 'Cotizaciones',
                        href: '/quotes',
                        permission: 'sales.quotes.view',
                        feature: 'sales.quotes',
                    },
                    {
                        title: 'Devoluciones de Ventas',
                        href: '/sale-returns',
                        permission: 'sales.returns.view',
                    },
                ],
            },
            {
                title: 'Análisis',
                href: '#',
                icon: BarChart3,
                items: [
                    {
                        title: 'Cuentas por Cobrar',
                        href: '/accounts-receivable',
                        permission: 'sales.receivables.view',
                    },
                    {
                        title: 'CxC por Cliente',
                        href: '/accounts-receivable/by-customer',
                        permission: 'sales.receivables.view',
                    },
                ],
            },
        ],
    },
    {
        title: 'Finanzas',
        href: '#',
        icon: Building2,
        permission: 'finance.accounts.view',
        items: [
            {
                title: 'Crear',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Bancos',
                        href: '/bank-accounts',
                        permission: 'finance.accounts.view',
                    },
                ],
            },
            {
                title: 'Transacciones',
                href: '#',
                icon: ArrowRightLeft,
                items: [
                    {
                        title: 'Pagos',
                        href: '/payments',
                        permission: 'purchases.payments.view',
                    },
                    {
                        title: 'Cobranza',
                        href: '/receipts',
                        permission: 'sales.receipts.view',
                    },
                    {
                        title: 'Pago Nómina',
                        href: '/payrolls',
                        permission: 'payroll.payrolls.view',
                    },
                ],
            },
            {
                title: 'Análisis',
                href: '#',
                icon: BarChart3,
                items: [
                    {
                        title: 'Movimientos Bancarios',
                        href: '/bank-transaction-analysis',
                        permission: 'finance.analysis.view',
                    },
                    {
                        title: 'Cierre de Caja',
                        href: '/cash-closing',
                        permission: 'finance.cash_closing.view',
                    },
                ],
            },
        ],
    },
    {
        title: 'Gastos',
        href: '#',
        icon: CircleDollarSign,
        permission: 'expenses.expenses.view',
        items: [
            {
                title: 'Categorías',
                href: '/expense-categories',
                permission: 'expenses.categories.view',
            },
            {
                title: 'Gastos',
                href: '/expenses',
                permission: 'expenses.expenses.view',
            },
        ],
    },
    /*
    {
        title: 'Nómina',
        href: '#',
        icon: Wallet,
        items: [
            {
                title: 'Períodos',
                href: '/payroll-periods',
            },
            {
                title: 'Nóminas',
                href: '/payrolls',
            },
            {
                title: 'Préstamos',
                href: '/employee-loans',
            },
            {
                title: 'Daños',
                href: '/employee-damages',
            },
            {
                title: 'Empleados',
                href: '/employees',
            },
        ],
    },*/
    {
        title: 'Configuración',
        href: '#',
        icon: Settings,
        permission: 'settings.company.view',
        items: [
            {
                title: 'Datos de la Empresa',
                href: '/settings/company',
                permission: 'settings.company.view',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];

const { hasFeature } = useTenantFeatures();
const { can } = usePermissions();

function filterNavItems(items: NavItem[]): NavItem[] {
    return items
        .filter(
            (item) =>
                (!item.feature || hasFeature(item.feature)) &&
                (!item.permission || can(item.permission)),
        )
        .map((item) =>
            item.items ? { ...item, items: filterNavItems(item.items) } : item,
        );
}

const visibleNavItems = computed<NavItem[]>(() => filterNavItems(mainNavItems));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
