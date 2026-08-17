<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Warehouse } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { dashboard } from '@/routes';
import type { StockDetailRow, WarehouseStock } from '@/types/Dashboard';

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
            {
                title: 'Existencia por almacén',
                href: '/dashboard/inventario/existencia-almacen',
            },
        ],
    },
});

const props = defineProps<{
    stockByWarehouse: WarehouseStock[];
    stockDetail: StockDetailRow[];
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/inventario/existencia-almacen',
);

const selectedWarehouseId = ref<number | 'all'>('all');

const filteredRows = computed<StockDetailRow[]>(() => {
    if (selectedWarehouseId.value === 'all') {
        return props.stockDetail;
    }

    return props.stockDetail.filter(
        (row) => row.war_id === selectedWarehouseId.value,
    );
});

const totalQuantity = computed(() =>
    props.stockByWarehouse.reduce(
        (total, warehouse) => total + warehouse.total_quantity,
        0,
    ),
);

const columns: DataTableColumn[] = [
    { key: 'pro_code', label: 'Código' },
    { key: 'pro_name', label: 'Producto' },
    { key: 'war_name', label: 'Almacén' },
    {
        key: 'quantity',
        label: 'Cantidad',
        align: 'right',
        class: 'tabular-nums',
    },
];

function formatQuantity(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
}
</script>

<template>
    <Head title="Existencia por Almacén" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Existencia por almacén"
            description="Unidades disponibles por producto en cada almacén"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <StatCard
                label="Total de unidades"
                :value="totalQuantity"
                :icon="Warehouse"
            />
            <StatCard
                label="Almacenes"
                :value="stockByWarehouse.length"
                :icon="Warehouse"
            />
        </div>

        <label class="flex w-fit items-center gap-2">
            <span class="text-sm text-muted-foreground">Almacén</span>
            <select
                v-model="selectedWarehouseId"
                class="h-8 cursor-pointer appearance-none rounded-lg border border-input bg-background py-1.5 pr-9 pl-3 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <option value="all">Todos</option>
                <option
                    v-for="warehouse in stockByWarehouse"
                    :key="warehouse.warehouse_id"
                    :value="warehouse.warehouse_id"
                >
                    {{ warehouse.war_name }}
                </option>
            </select>
        </label>

        <DataTable
            :columns="columns"
            :rows="filteredRows"
            empty-text="No hay existencias registradas"
        >
            <template #quantity="{ row }">
                <span class="font-medium tabular-nums">
                    {{ formatQuantity(row.quantity) }}
                </span>
            </template>
        </DataTable>
    </div>
</template>
