<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Coins, Handshake, Scale, TrendingUp } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
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
                title: 'Finanzas',
                href: '/dashboard/finanzas',
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
    '/dashboard/finanzas',
);

</script>

<template>
    <Head title="Finanzas - Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Finanzas"
            description="Saldos de cuentas por cobrar/pagar y rentabilidad del período"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
                label="CxC Clientes"
                :amounts="kpis.receivables.balance"
                variant="info"
                :icon="Handshake"
                @click="goTo('/analytics/receivables')"
            />
            <KpiCard
                label="CxP Proveedores"
                :amounts="kpis.payables.balance"
                variant="danger"
                :icon="Scale"
                @click="goTo('/analytics/payables')"
            />
            <KpiCard
                label="Ganancia Bruta"
                :amounts="kpis.profitLoss.grossProfit.total"
                :trend="kpis.profitLoss.grossProfit.trend"
                variant="success"
                :icon="TrendingUp"
                @click="goTo('/analytics/products')"
            />
            <KpiCard
                label="Ganancia Neta"
                :amounts="kpis.profitLoss.netProfit.total"
                :trend="kpis.profitLoss.netProfit.trend"
                variant="info"
                :icon="Coins"
                @click="goTo('/analytics/cash-flow')"
            />
        </div>
    </div>
</template>
