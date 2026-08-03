<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { TrendingDown, TrendingUp, Wallet } from 'lucide-vue-next';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import {
    currencySymbol,
    formatAmount,
} from '@/composables/Finance/useCoinFormat';
import type {
    CurrencyMap,
    TrendData,
    CashFlowDetails,
} from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Flujo de Caja', href: '/analytics/cash-flow' },
        ],
    },
});

const props = defineProps<{
    summary: {
        inflow: CurrencyMap;
        outflow: CurrencyMap;
        net: CurrencyMap;
        trend: TrendData;
    };
    details?: CashFlowDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/cash-flow',
);

const accountColumns: DataTableColumn[] = [
    { key: 'bank_name', label: 'Cuenta' },
    { key: 'btr_coin', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'tabular-nums' },
];

const netColumns: DataTableColumn[] = [
    { key: 'bank_name', label: 'Cuenta' },
    { key: 'bank_currency', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Neto', align: 'right', class: 'tabular-nums' },
];
</script>

<template>
    <Head title="Flujo de Caja" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Flujo de Caja"
            description="Ingresos, egresos y flujo neto del período seleccionado"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 md:grid-cols-3">
            <KpiCard
                label="Ingresos"
                :amounts="summary.inflow"
                variant="success"
                :icon="TrendingUp"
            />
            <KpiCard
                label="Egresos"
                :amounts="summary.outflow"
                variant="danger"
                :icon="TrendingDown"
            />
            <KpiCard
                label="Flujo Neto"
                :amounts="summary.net"
                :trend="summary.trend"
                variant="info"
                :icon="Wallet"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Ingresos por Cuenta</CardTitle>
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
                            :columns="accountColumns"
                            :rows="details?.inflowByAccount ?? []"
                            empty-text="Sin ingresos en este período"
                        >
                            <template #total="{ row }">
                                <span class="font-medium">
                                    {{ currencySymbol(row.btr_coin) }}
                                    {{ formatAmount(row.total) }}
                                </span>
                            </template>
                        </DataTable>
                    </Deferred>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Egresos por Cuenta</CardTitle>
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
                            :columns="accountColumns"
                            :rows="details?.outflowByAccount ?? []"
                            empty-text="Sin egresos en este período"
                        >
                            <template #total="{ row }">
                                <span class="font-medium">
                                    {{ currencySymbol(row.btr_coin) }}
                                    {{ formatAmount(row.total) }}
                                </span>
                            </template>
                        </DataTable>
                    </Deferred>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Neto por Cuenta Bancaria</CardTitle>
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
                        :columns="netColumns"
                        :rows="details?.netByAccount ?? []"
                        empty-text="Sin movimientos en este período"
                    >
                        <template #total="{ row }">
                            <Badge
                                :variant="
                                    row.total >= 0 ? 'default' : 'destructive'
                                "
                            >
                                {{ currencySymbol(row.bank_currency) }}
                                {{ formatAmount(Math.abs(row.total)) }}
                                {{ row.total >= 0 ? '' : '(negativo)' }}
                            </Badge>
                        </template>
                    </DataTable>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>
