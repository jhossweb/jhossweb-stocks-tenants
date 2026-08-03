<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CalendarRange,
    ClipboardList,
    ShoppingCart,
    Users,
} from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { dashboard } from '@/routes';
import type { DashboardKpis } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Compras',
                href: '/dashboard/compras',
            },
        ],
    },
});

const props = defineProps<{
    kpis: DashboardKpis;
    selectedMonth: string;
}>();

const { monthOptions, goTo, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/compras',
);
</script>

<template>
    <Head title="Compras - Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras"
            description="Movimiento de compras y proveedores del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
                label="Compras Totales"
                :amounts="kpis.purchases.total"
                :trend="kpis.purchases.trend"
                variant="success"
                :icon="ShoppingCart"
                @click="goTo('/analytics/purchases-total')"
            />
            <StatCard
                label="Proveedores Activos"
                :value="kpis.purchases.supplierCount"
                :icon="Users"
                @click="goTo('/analytics/purchases-supplier')"
            />
            <KpiCard
                label="Compras por Mes"
                :amounts="kpis.purchases.total"
                :trend="kpis.purchases.trend"
                variant="info"
                :icon="CalendarRange"
                @click="goTo('/analytics/purchases-month')"
            />
            <KpiCard
                label="Compras por Estado"
                :amounts="kpis.purchases.pending"
                variant="danger"
                :icon="ClipboardList"
                @click="goTo('/analytics/purchases-status')"
            />
        </div>
    </div>
</template>
