<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { Clock3, ShoppingCart } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
            {
                title: 'Compras por Estado',
                href: '/analytics/purchases-status',
            },
        ],
    },
});

const props = defineProps<{
    summary: {
        total: CurrencyMap;
        trend: TrendData;
        pending: CurrencyMap;
    };
    details?: PurchaseDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/purchases-status',
);

const statusFilter = ref('all');

const filteredByStatus = computed(() => {
    const items = props.details?.byStatus ?? [];

    if (statusFilter.value === 'all') {
        return items;
    }

    return items.filter((item) => item.invoice_status === statusFilter.value);
});

const columns: DataTableColumn[] = [
    { key: 'invoice_status', label: 'Estado' },
    { key: 'invoice_coin', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'tabular-nums' },
];

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

function statusLabel(status: string): string {
    const map: Record<string, string> = {
        paid: 'Pagado',
        partial: 'Parcial',
        pending: 'Pendiente',
    };

    return map[status] ?? status;
}
</script>

<template>
    <Head title="Compras por Estado" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras por Estado"
            description="Desglose de compras según su estado de pago en el período seleccionado"
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
            <KpiCard
                label="Pendiente"
                :amounts="summary.pending"
                variant="danger"
                :icon="Clock3"
            />
        </div>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>Desglose por Estado</CardTitle>
                    <label class="flex min-h-8 items-center gap-2">
                        <span class="sr-only">Filtrar por estado</span>
                        <select
                            v-model="statusFilter"
                            class="h-8 cursor-pointer rounded-lg border border-input bg-background px-3 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="all">Todos los estados</option>
                            <option value="paid">Pagado</option>
                            <option value="partial">Parcial</option>
                            <option value="pending">Pendiente</option>
                        </select>
                    </label>
                </div>
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
                        :rows="filteredByStatus"
                        :empty-text="
                            details?.byStatus?.length
                                ? 'Ninguna compra coincide con el filtro'
                                : 'Sin compras registradas en ' +
                                  monthLabel(selectedMonth)
                        "
                    >
                        <template #invoice_status="{ row }">
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                :class="statusBadge(row.invoice_status)"
                            >
                                {{ statusLabel(row.invoice_status) }}
                            </span>
                        </template>
                        <template #total="{ row }">
                            <span class="font-medium">
                                {{ currencySymbol(row.invoice_coin) }}
                                {{ formatAmount(row.total) }}
                            </span>
                        </template>
                    </DataTable>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>
