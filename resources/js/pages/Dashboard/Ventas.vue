<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { TrendingDown } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import ProductSummaryCard from '@/components/Dashboard/ProductSummaryCard.vue';
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
                title: 'Ventas',
                href: '/dashboard/ventas',
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
    '/dashboard/ventas',
);
</script>

<template>
    <Head title="Ventas - Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Ventas"
            description="Rentabilidad y desempeño de productos del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 md:grid-cols-3">
            <KpiCard
                label="Costo Ventas"
                :amounts="kpis.profitLoss.cogs.total"
                :trend="kpis.profitLoss.cogs.trend"
                variant="danger"
                :icon="TrendingDown"
                @click="goTo('/analytics/products')"
            />
        </div>

        <ProductSummaryCard
            :products="kpis.products"
            @click="goTo('/analytics/products')"
        />
    </div>
</template>
