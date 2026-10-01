<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Boxes,
    Receipt,
    ShoppingCart,
    TrendingUp,
    Wallet,
} from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { useCurrencyMode } from '@/composables/Dashboard/useCurrencyMode';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { dashboard } from '@/routes';
import type { CategoryAggregates } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    categoryAggregates: CategoryAggregates;
    selectedMonth: string;
}>();

const { monthOptions, goTo, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    dashboard().url,
);

const { mode, isUsd, setMode } = useCurrencyMode();
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Dashboard"
            description="Resumen general de la operación del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            :currency-mode="mode"
            :rate-date="categoryAggregates.gastos.rate_date"
            @change-month="changeMonth"
            @change-currency-mode="setMode"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <KpiCard
                label="Ventas diarias"
                :amounts="categoryAggregates.ventas.total"
                :usd="categoryAggregates.ventas.usd"
                :is-usd-mode="isUsd"
                variant="success"
                :icon="TrendingUp"
                @click="goTo('/dashboard/ventas-diarias')"
            />
            <KpiCard
                label="Gastos"
                :amounts="categoryAggregates.gastos.total"
                :usd="categoryAggregates.gastos.usd"
                :trend="categoryAggregates.gastos.trend"
                :is-usd-mode="isUsd"
                variant="danger"
                :icon="Receipt"
                @click="goTo('/dashboard/gastos')"
            />
            <KpiCard
                label="Compras"
                :amounts="categoryAggregates.compras.total"
                :usd="categoryAggregates.compras.usd"
                :trend="categoryAggregates.compras.trend"
                :is-usd-mode="isUsd"
                :icon="ShoppingCart"
                @click="goTo('/dashboard/compras')"
            />
            <KpiCard
                label="Finanzas"
                :amounts="categoryAggregates.finanzas.total"
                :usd="categoryAggregates.finanzas.usd"
                :is-usd-mode="isUsd"
                variant="info"
                :icon="Wallet"
                @click="goTo('/dashboard/finanzas')"
            />
            <KpiCard
                label="Inventario"
                :amounts="categoryAggregates.inventario.total"
                :is-usd-mode="isUsd"
                :icon="Boxes"
                @click="goTo('/dashboard/inventario')"
            />
        </div>
    </div>
</template>
