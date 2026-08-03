<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { AlertTriangle, Package } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import type { ProductDetails, LowStockItem } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Ventas', href: '/dashboard/ventas' },
            { title: 'Analítica de Productos', href: '/analytics/products' },
        ],
    },
});

const props = defineProps<{
    summary: {
        totalSkus: number;
        lowStock: LowStockItem[];
    };
    details?: ProductDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/products',
);

const allLowStock = (): LowStockItem[] =>
    props.details?.lowStock ?? props.summary.lowStock;

const productColumns: DataTableColumn[] = [
    { key: 'pro_name', label: 'Producto' },
    {
        key: 'quantity',
        label: 'Cantidad',
        align: 'right',
        class: 'tabular-nums',
    },
];

const lowStockColumns: DataTableColumn[] = [
    { key: 'pro_name', label: 'Producto' },
    {
        key: 'stk_cant',
        label: 'Stock Actual',
        align: 'right',
        class: 'tabular-nums',
    },
    {
        key: 'pro_cant_min',
        label: 'Stock Mínimo',
        align: 'right',
        class: 'tabular-nums',
    },
    {
        key: 'difference',
        label: 'Diferencia',
        align: 'right',
        class: 'tabular-nums',
    },
];

function formatCount(value: number): string {
    return new Intl.NumberFormat('es', {
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Analítica de Productos" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Analítica de Productos"
            description="Desempeño de productos e inventario del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <StatCard
                label="Total SKUs"
                :value="summary.totalSkus"
                :icon="Package"
            />
            <StatCard
                label="Stock Bajo"
                :value="summary.lowStock.length"
                :description="
                    summary.lowStock.length === 0
                        ? 'Sin alertas'
                        : summary.lowStock.length +
                          ' producto(s) bajo el mínimo'
                "
                variant="danger"
                :icon="AlertTriangle"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Más Vendidos</CardTitle>
                </CardHeader>
                <CardContent>
                    <Deferred data="details">
                        <template #fallback>
                            <div class="flex items-center justify-center py-8">
                                <div
                                    class="size-6 animate-pulse rounded bg-muted-foreground/20"
                                />
                            </div>
                        </template>

                        <DataTable
                            :columns="productColumns"
                            :rows="details?.topSelling ?? []"
                            empty-text="Sin ventas en este período"
                        >
                            <template #quantity="{ row }">
                                <span class="font-medium">{{
                                    formatCount(row.quantity)
                                }}</span>
                            </template>
                        </DataTable>
                    </Deferred>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Menos Vendidos</CardTitle>
                </CardHeader>
                <CardContent>
                    <Deferred data="details">
                        <template #fallback>
                            <div class="flex items-center justify-center py-8">
                                <div
                                    class="size-6 animate-pulse rounded bg-muted-foreground/20"
                                />
                            </div>
                        </template>

                        <DataTable
                            :columns="productColumns"
                            :rows="details?.worstSelling ?? []"
                            empty-text="Sin ventas en este período"
                        >
                            <template #quantity="{ row }">
                                <span class="font-medium">{{
                                    formatCount(row.quantity)
                                }}</span>
                            </template>
                        </DataTable>
                    </Deferred>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>
                    <span class="flex items-center gap-2">
                        <AlertTriangle
                            v-if="allLowStock().length > 0"
                            class="size-4 text-amber-500"
                            aria-hidden="true"
                        />
                        Productos con Stock Bajo
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable
                    :columns="lowStockColumns"
                    :rows="
                        allLowStock().map((item) => ({
                            ...item,
                            difference: item.stk_cant - item.pro_cant_min,
                        }))
                    "
                    empty-text="No hay productos con stock bajo"
                >
                    <template #difference="{ row }">
                        <span class="font-medium text-red-500">{{
                            formatCount(row.difference)
                        }}</span>
                    </template>
                </DataTable>
            </CardContent>
        </Card>
    </div>
</template>
