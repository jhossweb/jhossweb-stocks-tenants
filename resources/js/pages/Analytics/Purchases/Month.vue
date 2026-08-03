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
            { title: 'Compras por Mes', href: '/analytics/purchases-month' },
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
    '/analytics/purchases-month',
);

const columns: DataTableColumn[] = [
    { key: 'month', label: 'Mes' },
    { key: 'invoice_coin', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'tabular-nums' },
];
</script>

<template>
    <Head title="Compras por Mes" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras por Mes"
            description="Histórico de compras de los últimos 6 meses"
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
                <CardTitle>Últimos 6 meses</CardTitle>
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
                        :rows="details?.byMonth ?? []"
                        empty-text="Sin datos de compras mensuales"
                    >
                        <template #month="{ row }">
                            <span class="font-medium">{{
                                monthLabel(row.month)
                            }}</span>
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
