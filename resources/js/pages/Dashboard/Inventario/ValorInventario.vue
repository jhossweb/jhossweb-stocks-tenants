<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Boxes } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { formatAmount } from '@/composables/Finance/useCoinFormat';
import { dashboard } from '@/routes';
import type { CurrencyMap, InventoryValueItem } from '@/types/Dashboard';

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
                title: 'Valor del inventario',
                href: '/dashboard/inventario/valor-inventario',
            },
        ],
    },
});

const props = defineProps<{
    inventoryValue: CurrencyMap;
    valueDetail: InventoryValueItem[];
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/inventario/valor-inventario',
);

const columns: DataTableColumn[] = [
    { key: 'pro_code', label: 'Código' },
    { key: 'pro_name', label: 'Producto' },
    {
        key: 'total_quantity',
        label: 'Unidades',
        align: 'right',
        class: 'tabular-nums',
    },
    {
        key: 'unit_cost_ref',
        label: 'Costo unit. (USD)',
        align: 'right',
        class: 'tabular-nums',
    },
    {
        key: 'total_value_ref',
        label: 'Valor (USD)',
        align: 'right',
        class: 'tabular-nums',
    },
];

function formatValue(value: number): string {
    return formatAmount(Number(value));
}
</script>

<template>
    <Head title="Valor del Inventario" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Valor del inventario"
            description="Valoración de existencias según el último costo registrado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <KpiCard
            label="Valor total del inventario"
            :amounts="inventoryValue"
            :icon="Boxes"
        />

        <DataTable
            :columns="columns"
            :rows="valueDetail"
            empty-text="No hay existencias valoradas"
        >
            <template #total_quantity="{ row }">
                <span class="font-medium tabular-nums">
                    {{ formatAmount(row.total_quantity) }}
                </span>
            </template>
            <template #unit_cost_ref="{ row }">
                <span class="tabular-nums">
                    $ {{ formatValue(row.unit_cost_ref) }}
                </span>
            </template>
            <template #total_value_ref="{ row }">
                <span class="font-medium tabular-nums">
                    $ {{ formatValue(row.total_value_ref) }}
                </span>
            </template>
        </DataTable>
    </div>
</template>
