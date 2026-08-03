<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { Coins, Handshake } from 'lucide-vue-next';
import { computed } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { formatAmount } from '@/composables/Finance/useCoinFormat';
import type { CurrencyMap, ReceivableDetails } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cuentas por Cobrar', href: '/analytics/receivables' },
        ],
    },
});

const props = defineProps<{
    summary: {
        balance: Record<string, { balance: number; balance_usd: number }>;
        collected: CurrencyMap;
    };
    details?: ReceivableDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/receivables',
);

const balanceByCurrency = computed<CurrencyMap>(() => {
    const map: CurrencyMap = {};

    for (const [coin, data] of Object.entries(props.summary.balance)) {
        map[coin as keyof CurrencyMap] = data.balance;
    }

    return map;
});

const columns: DataTableColumn[] = [
    { key: 'customer_name', label: 'Cliente' },
    { key: 'sls_coin', label: 'Moneda', align: 'right' },
    { key: 'balance', label: 'Saldo', align: 'right', class: 'tabular-nums' },
    {
        key: 'balance_usd',
        label: 'Saldo (USD)',
        align: 'right',
        class: 'tabular-nums',
    },
];
</script>

<template>
    <Head title="Cuentas por Cobrar" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Cuentas por Cobrar"
            description="Saldos pendientes de clientes y cobros del período"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <KpiCard
                label="Saldo Pendiente"
                :amounts="balanceByCurrency"
                variant="info"
                :icon="Handshake"
            />
            <KpiCard
                label="Cobrado este Mes"
                :amounts="summary.collected"
                variant="success"
                :icon="Coins"
            />
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Clientes con Saldo Pendiente</CardTitle>
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
                        :rows="details?.byCustomer ?? []"
                        empty-text="No hay cuentas por cobrar pendientes"
                    >
                        <template #balance="{ row }">
                            <span class="font-medium"
                                >$ {{ formatAmount(row.balance) }}</span
                            >
                        </template>
                        <template #balance_usd="{ row }">
                            <span>$ {{ formatAmount(row.balance_usd) }}</span>
                        </template>
                    </DataTable>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>
