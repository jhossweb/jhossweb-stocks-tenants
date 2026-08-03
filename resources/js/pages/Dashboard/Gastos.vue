<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Banknote, Receipt } from 'lucide-vue-next';
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
                title: 'Gastos',
                href: '/dashboard/gastos',
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
    '/dashboard/gastos',
);
</script>

<template>
    <Head title="Gastos - Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Gastos"
            description="Desglose de gastos fijos y variables del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <KpiCard
                label="Gastos Fijos"
                :amounts="kpis.expensesFijo.total"
                :trend="kpis.expensesFijo.trend"
                variant="danger"
                :icon="Receipt"
                @click="goTo('/analytics/expenses-fijo')"
            />
            <KpiCard
                label="Gastos Variables"
                :amounts="kpis.expensesVariable.total"
                :trend="kpis.expensesVariable.trend"
                variant="danger"
                :icon="Banknote"
                @click="goTo('/analytics/expenses-variable')"
            />
        </div>
    </div>
</template>
