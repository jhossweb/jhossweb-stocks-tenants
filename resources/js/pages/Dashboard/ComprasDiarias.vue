<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, ShoppingCart } from 'lucide-vue-next';
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
import type { CurrencyMap, PurchasesPage } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Compras',
                href: '/dashboard/compras',
            },
            {
                title: 'Compras diarias',
                href: '/dashboard/compras-diarias',
            },
        ],
    },
});

const props = defineProps<{
    purchases: PurchasesPage;
    today: CurrencyMap;
    selectedMonth: string;
    from: string;
    to: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/dashboard/compras-diarias',
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
            '/dashboard/compras-diarias',
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
    { key: 'invoice_date', label: 'Fecha' },
    { key: 'invoice_number', label: 'Nº Factura' },
    { key: 'supplier', label: 'Proveedor' },
    {
        key: 'invoice_payment_condition',
        label: 'Condición',
        align: 'center',
    },
    { key: 'invoice_coin', label: 'Moneda', align: 'center' },
    { key: 'invoice_total', label: 'Total', align: 'right' },
    { key: 'invoice_status', label: 'Estado', align: 'center' },
];
</script>

<template>
    <Head title="Compras por día" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras por día"
            description="Compras del período seleccionado, desglosadas por factura"
            :month="selectedMonth"
            :month-options="monthOptions"
            @change-month="changeMonth"
        />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <KpiCard
                label="Compras de hoy"
                :amounts="today"
                variant="success"
                :icon="ShoppingCart"
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
                <CardTitle>Compras</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable
                    :columns="columns"
                    :rows="purchases.data"
                    :empty-text="'Sin compras en el período seleccionado'"
                >
                    <template #invoice_date="{ row }">
                        <span
                            class="font-medium whitespace-nowrap tabular-nums"
                        >
                            {{ formatDate(row.invoice_date) }}
                        </span>
                    </template>
                    <template #invoice_number="{ row }">
                        <span class="whitespace-nowrap tabular-nums">
                            {{ row.invoice_number }}
                        </span>
                    </template>
                    <template #supplier="{ row }">
                        <span class="text-muted-foreground">
                            {{ row.supplier?.supplier_name ?? '—' }}
                        </span>
                    </template>
                    <template #invoice_payment_condition="{ row }">
                        <span
                            :class="
                                row.invoice_payment_condition === 'credit'
                                    ? 'text-amber-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                paymentConditionLabel(
                                    row.invoice_payment_condition,
                                )
                            }}
                        </span>
                    </template>
                    <template #invoice_coin="{ row }">
                        <span
                            class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium"
                        >
                            {{ row.invoice_coin }}
                        </span>
                    </template>
                    <template #invoice_total="{ row }">
                        <span class="whitespace-nowrap tabular-nums">
                            {{
                                formatCoinValue(
                                    Number(row.invoice_total),
                                    row.invoice_coin,
                                )
                            }}
                        </span>
                    </template>
                    <template #invoice_status="{ row }">
                        <span
                            class="rounded-full px-2 py-0.5 text-xs"
                            :class="statusClass(row.invoice_status)"
                        >
                            {{ row.invoice_status.toUpperCase() }}
                        </span>
                    </template>
                </DataTable>
            </CardContent>
            <CardFooter
                v-if="purchases.total > 0"
                class="flex flex-wrap items-center justify-between gap-4 border-t"
            >
                <p class="text-sm text-muted-foreground">
                    Mostrando
                    <span class="font-medium text-foreground">
                        {{ purchases.from }}-{{ purchases.to }}
                    </span>
                    de
                    <span class="font-medium text-foreground">
                        {{ purchases.total }}
                    </span>
                    compras
                </p>

                <div v-if="purchases.last_page > 1" class="flex gap-1">
                    <template v-for="(link, key) in purchases.links" :key="key">
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
