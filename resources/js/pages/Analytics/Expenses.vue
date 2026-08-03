<script lang="ts">
const titleMap: Record<string, string> = {
    expenses: 'Analítica de Gastos',
    'expenses-fijo': 'Gastos Fijos',
    'expenses-variable': 'Gastos Variables',
};

const breadcrumbMap: Record<string, string> = {
    expenses: '/analytics/expenses',
    'expenses-fijo': '/analytics/expenses-fijo',
    'expenses-variable': '/analytics/expenses-variable',
};

const labelMap: Record<string, string> = {
    expenses: 'Total Gastos',
    'expenses-fijo': 'Total Gastos Fijos',
    'expenses-variable': 'Total Gastos Variables',
};
</script>

<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { Clock3, Receipt } from 'lucide-vue-next';
import { computed } from 'vue';
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
import type { CurrencyMap, TrendData, ExpenseDetails } from '@/types/Dashboard';

defineOptions({
    layout: (props: { analyticsType?: string }) => ({
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gastos', href: '/dashboard/gastos' },
            {
                title:
                    titleMap[props.analyticsType ?? 'expenses'] ??
                    'Analítica de Gastos',
                href:
                    breadcrumbMap[props.analyticsType ?? 'expenses'] ??
                    '/analytics/expenses',
            },
        ],
    }),
});

const props = defineProps<{
    summary: {
        total: CurrencyMap;
        pending: CurrencyMap;
        trend: TrendData;
    };
    details?: ExpenseDetails;
    selectedMonth: string;
    analyticsType?: string;
}>();

const breadcrumbTitle = computed(
    () => titleMap[props.analyticsType ?? 'expenses'] ?? 'Analítica de Gastos',
);

const totalLabel = computed(
    () => labelMap[props.analyticsType ?? 'expenses'] ?? 'Total Gastos',
);

const currentPath =
    breadcrumbMap[props.analyticsType ?? 'expenses'] ?? '/analytics/expenses';

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    currentPath,
);

const columns: DataTableColumn[] = [
    { key: 'exc_name', label: 'Categoría' },
    { key: 'exc_type', label: 'Tipo' },
    { key: 'exp_coin', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'tabular-nums' },
];
</script>

<template>
    <Head :title="breadcrumbTitle" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            :title="breadcrumbTitle"
            description="Desglose de gastos del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <KpiCard
                :label="totalLabel"
                :amounts="summary.total"
                :trend="summary.trend"
                variant="danger"
                :icon="Receipt"
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
                <CardTitle>Gastos por Categoría</CardTitle>
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
                        :rows="details?.byCategory ?? []"
                        :empty-text="
                            'Sin gastos registrados en ' +
                            monthLabel(selectedMonth)
                        "
                    >
                        <template #total="{ row }">
                            <span class="font-medium">
                                {{ currencySymbol(row.exp_coin) }}
                                {{ formatAmount(row.total) }}
                            </span>
                        </template>
                    </DataTable>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>
