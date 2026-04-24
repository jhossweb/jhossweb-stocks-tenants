<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, ShoppingCart, Undo2, FileText, ArrowRightLeft, BarChart3, Users, CreditCard, Building2 } from 'lucide-vue-next';
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
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
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
                title: 'Bancos',
                href: '/bank-accounts',
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
