<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    ShoppingCart,
    FileText,
    ArrowRightLeft,
    BarChart3,
    Users,
    Building2,
    PackageSearch,
    CircleDollarSign,
    Wallet,
    Settings,
} from 'lucide-vue-next';
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
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Productos',
                        href: '/products',
                    },
                    {
                        title: 'Categorías',
                        href: '/categories',
                    },
                    {
                        title: 'Marcas',
                        href: '/brands',
                    },
                    {
                        title: 'Almacenes',
                        href: '/warehouses',
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
                    },
                    {
                        title: 'Lista de Precios',
                        href: '/inventory/list-prices',
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
                    },
                    {
                        title: 'Ajustar Precios',
                        href: '/inventory/adjust-prices',
                    },
                ],
            },
        ],
    },
    {
        title: 'Compras',
        href: '#',
        icon: ShoppingCart,
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Proveedores',
                        href: '/suppliers', // Assuming this path, might need to be adjusted
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
                    },
                    {
                        title: 'Devoluciones',
                        href: '/purchase-returns',
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
                    },
                    {
                        title: 'CxP por Proveedor',
                        href: '/accounts-payable/by-supplier',
                    },
                ],
            },
        ],
    },
    {
        title: 'Ventas',
        href: '#',
        icon: Users,
        items: [
            {
                title: 'Fichero',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Clientes',
                        href: '/customers',
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
                    },
                    {
                        title: 'Cotizaciones',
                        href: '/quotes',
                    },
                    {
                        title: 'Devoluciones de Ventas',
                        href: '/sale-returns',
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
                    },
                    {
                        title: 'CxC por Cliente',
                        href: '/accounts-receivable/by-customer',
                    },
                ],
            },
        ],
    },
    {
        title: 'Finanzas',
        href: '#',
        icon: Building2,
        items: [
            {
                title: 'Crear',
                href: '#',
                icon: FileText,
                items: [
                    {
                        title: 'Bancos',
                        href: '/bank-accounts',
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
                    },
                    {
                        title: 'Cobranza',
                        href: '/receipts',
                    },
                    {
                        title: 'Pago Nómina',
                        href: '/payrolls',
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
                    },
                    {
                        title: 'Cierre de Caja',
                        href: '/cash-closing',
                    },
                ],
            },
        ],
    },
    {
        title: 'Gastos',
        href: '#',
        icon: CircleDollarSign,
        items: [
            {
                title: 'Categorías',
                href: '/expense-categories',
            },
            {
                title: 'Gastos',
                href: '/expenses',
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
        items: [
            {
                title: 'Datos de la Empresa',
                href: '/settings/company',
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
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
