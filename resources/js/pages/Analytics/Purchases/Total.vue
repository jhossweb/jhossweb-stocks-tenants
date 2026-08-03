<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { ShoppingCart } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    useDashboardMonth,
    monthLabel,
} from '@/composables/Dashboard/useDashboardMonth';
import {
    currencySymbol,
    formatAmount,
} from '@/composables/Finance/useCoinFormat';
import type {
    CurrencyMap,
    TrendData,
    PurchaseDetails,
} from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Compras', href: '/dashboard/compras' },
            { title: 'Compras Totales', href: '/analytics/purchases-total' },
        ],
    },
});

const props = defineProps<{
    summary: {
        total: CurrencyMap;
        trend: TrendData;
    };
    details?: PurchaseDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/purchases-total',
);

const columns: DataTableColumn[] = [
    { key: 'invoice_number', label: 'Factura' },
    { key: 'invoice_date', label: 'Fecha' },
    { key: 'supplier_name', label: 'Proveedor' },
    { key: 'invoice_coin', label: 'Moneda', align: 'right' },
    {
        key: 'invoice_total',
        label: 'Total',
        align: 'right',
        class: 'tabular-nums',
    },
    { key: 'invoice_status', label: 'Estado', align: 'center' },
];

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('es', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function statusBadge(status: string): string {
    const map: Record<string, string> = {
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        partial:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pending: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    return (
        map[status] ??
        'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
    );
}
</script>

<template>
    <Head title="Compras Totales" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras Totales"
            description="Todas las compras registradas en el período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 md:grid-cols-3">
            <KpiCard
                label="Total Compras"
                :amounts="summary.total"
                :trend="summary.trend"
                variant="success"
                :icon="ShoppingCart"
            />
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Todas las Compras</CardTitle>
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
                        :columns="columns"
                        :rows="details?.purchases ?? []"
                        :empty-text="
                            'Sin compras registradas en ' +
                            monthLabel(selectedMonth)
                        "
                    >
                        <template #invoice_date="{ row }">
                            <span class="text-muted-foreground">{{
                                formatDate(row.invoice_date)
                            }}</span>
                        </template>
                        <template #invoice_total="{ row }">
                            <span class="font-medium">
                                {{ currencySymbol(row.invoice_coin) }}
                                {{ formatAmount(row.invoice_total) }}
                            </span>
                        </template>
                        <template #invoice_status="{ row }">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                :class="statusBadge(row.invoice_status)"
                            >
                                {{ row.invoice_status }}
                            </span>
                        </template>
                    </DataTable>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>
