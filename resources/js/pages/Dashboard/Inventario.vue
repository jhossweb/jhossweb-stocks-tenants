<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes, Warehouse } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { dashboard } from '@/routes';
import type { InventoryKpis } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Inventario',
                href: '/dashboard/inventario',
            },
        ],
    },
});

const props = defineProps<{
    kpis: InventoryKpis;
    selectedMonth: string;
}>();

const { monthOptions, goTo, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/inventario',
);

const totalQuantity = computed(() =>
    props.kpis.stockByWarehouse.reduce(
        (total, warehouse) => total + warehouse.total_quantity,
        0,
    ),
);
</script>

<template>
    <Head title="Inventario" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Inventario"
            description="Existencias y valoración actual del inventario"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <StatCard
                label="Existencia por almacén"
                :value="totalQuantity"
                :description="`${kpis.stockByWarehouse.length} almacén(es)`"
                :icon="Warehouse"
                @click="goTo('/dashboard/inventario/existencia-almacen')"
            />
            <KpiCard
                label="Valor del inventario"
                :amounts="kpis.inventoryValue"
                :icon="Boxes"
                @click="goTo('/dashboard/inventario/valor-inventario')"
            />
        </div>
    </div>
</template>
