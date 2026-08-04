<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, TrendingUp } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import DataTable from '@/components/DataTable/DataTable.vue';
import type { DataTableColumn } from '@/components/DataTable/DataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useDashboardMonth } from '@/composables/Dashboard/useDashboardMonth';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import { formatDate, today as todayIso } from '@/composables/useDate';
import { dashboard } from '@/routes';
import type { CurrencyMap, SalesPage } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Ventas diarias',
                href: '/dashboard/ventas-diarias',
            },
        ],
    },
});

const props = defineProps<{
    sales: SalesPage;
    today: CurrencyMap;
    selectedMonth: string;
    from: string;
    to: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/ventas-diarias',
);

const dateFrom = ref(props.from);
const dateTo = ref(props.to);

watch(
    () => [props.from, props.to],
    () => {
        dateFrom.value = props.from;
        dateTo.value = props.to;
    },
);

let filterTimeout: ReturnType<typeof setTimeout>;

watch([dateFrom, dateTo], () => {
    clearTimeout(filterTimeout);

    filterTimeout = setTimeout(() => {
        const from = dateFrom.value;
        const to = dateTo.value;

        if (!from || !to) {
            return;
        }

        if (from === props.from && to === props.to) {
            return;
        }

        router.get(
            '/dashboard/ventas-diarias',
            { from, to },
            { preserveState: true, replace: true, preserveScroll: true },
        );
    }, 300);
});

function resetToToday(): void {
    dateFrom.value = todayIso();
    dateTo.value = todayIso();
}

function paymentConditionLabel(condition: string): string {
    return condition === 'credit' ? 'Crédito' : 'Contado';
}

function statusClass(status: string): string {
    switch (status) {
        case 'paid':
            return 'bg-green-900/30 text-green-400';
        case 'partial':
            return 'bg-blue-900/30 text-blue-400';
        case 'pending':
            return 'bg-red-900/30 text-red-400';
        default:
            return 'bg-zinc-700/30 text-zinc-400';
    }
}

function linkLabel(label: string): string {
    return label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
}

const columns: DataTableColumn[] = [
    { key: 'sls_date', label: 'Fecha' },
    { key: 'sls_number', label: 'Nº Factura' },
    { key: 'customer', label: 'Cliente' },
    { key: 'sls_payment_condition', label: 'Condición', align: 'center' },
    { key: 'sls_coin', label: 'Moneda', align: 'center' },
    { key: 'sls_total', label: 'Total', align: 'right' },
    { key: 'sls_status', label: 'Estado', align: 'center' },
];
</script>

<template>
    <Head title="Ventas por día" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Ventas por día"
            description="Ventas del período seleccionado, desglosadas por factura"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
                label="Ventas de hoy"
                :amounts="today"
                variant="success"
                :icon="TrendingUp"
            />
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <CalendarDays class="size-4" aria-hidden="true" />
                    Rango de fechas
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-muted-foreground">Desde</span>
                        <Input v-model="dateFrom" type="date" class="w-44" />
                    </div>
                    <span class="pb-2 text-muted-foreground">—</span>
                    <div class="flex flex-col gap-1.5">
                        <span class="text-xs text-muted-foreground">Hasta</span>
                        <Input v-model="dateTo" type="date" class="w-44" />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="ml-2"
                        @click="resetToToday"
                    >
                        Hoy
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Ventas</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable
                    :columns="columns"
                    :rows="sales.data"
                    :empty-text="'Sin ventas en el período seleccionado'"
                >
                    <template #sls_date="{ row }">
                        <span
                            class="font-medium whitespace-nowrap tabular-nums"
                        >
                            {{ formatDate(row.sls_date) }}
                        </span>
                    </template>
                    <template #sls_number="{ row }">
                        <span class="whitespace-nowrap tabular-nums">
                            {{ row.sls_number }}
                        </span>
                    </template>
                    <template #customer="{ row }">
                        <span class="text-muted-foreground">
                            {{ row.customer?.clt_name ?? '—' }}
                        </span>
                    </template>
                    <template #sls_payment_condition="{ row }">
                        <span
                            :class="
                                row.sls_payment_condition === 'credit'
                                    ? 'text-amber-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                paymentConditionLabel(row.sls_payment_condition)
                            }}
                        </span>
                    </template>
                    <template #sls_coin="{ row }">
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                        >
                            {{ row.sls_coin }}
                        </span>
                    </template>
                    <template #sls_total="{ row }">
                        <span class="whitespace-nowrap tabular-nums">
                            {{
                                formatCoinValue(
                                    Number(row.sls_total),
                                    row.sls_coin,
                                )
                            }}
                        </span>
                    </template>
                    <template #sls_status="{ row }">
                        <span
                            class="rounded-full px-2 py-0.5 text-xs"
                            :class="statusClass(row.sls_status)"
                        >
                            {{ row.sls_status.toUpperCase() }}
                        </span>
                    </template>
                </DataTable>
            </CardContent>
            <CardFooter
                v-if="sales.total > 0"
                class="flex flex-wrap items-center justify-between gap-4 border-t"
            >
                <p class="text-sm text-muted-foreground">
                    Mostrando
                    <span class="font-medium text-foreground">
                        {{ sales.from }}-{{ sales.to }}
                    </span>
                    de
                    <span class="font-medium text-foreground">
                        {{ sales.total }}
                    </span>
                    ventas
                </p>

                <div v-if="sales.last_page > 1" class="flex gap-1">
                    <template v-for="(link, key) in sales.links" :key="key">
                        <div
                            v-if="link.url === null"
                            class="cursor-not-allowed rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground/50"
                        >
                            {{ linkLabel(link.label) }}
                        </div>
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded-md border border-border px-3 py-1.5 text-sm text-muted-foreground transition-colors hover:bg-muted"
                            :class="{
                                'border-primary bg-primary font-semibold text-primary-foreground hover:bg-primary':
                                    link.active,
                            }"
                            preserve-scroll
                        >
                            {{ linkLabel(link.label) }}
                        </Link>
                    </template>
                </div>
            </CardFooter>
        </Card>
    </div>
</template>
