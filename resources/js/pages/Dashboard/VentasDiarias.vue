<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CalendarDays, TrendingUp } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import type { CurrencyMap, SalesByDay } from '@/types/Dashboard';

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
    daily: SalesByDay[];
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

const totals = computed(() =>
    props.daily.reduce(
        (acc, day) => ({
            count: acc.count + day.count,
            usd: acc.usd + day.usd,
            cop: acc.cop + day.cop,
            bs: acc.bs + day.bs,
        }),
        { count: 0, usd: 0, cop: 0, bs: 0 },
    ),
);

function resetToToday(): void {
    dateFrom.value = todayIso();
    dateTo.value = todayIso();
}

const columns: DataTableColumn[] = [
    { key: 'date', label: 'Fecha' },
    { key: 'count', label: 'Nº Ventas', align: 'center' },
    { key: 'usd', label: 'Total USD', align: 'right' },
    { key: 'cop', label: 'Total COP', align: 'right' },
    { key: 'bs', label: 'Total BS', align: 'right' },
];
</script>

<template>
    <Head title="Ventas por día" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Ventas por día"
            description="Venta del período seleccionado, desglosada por moneda"
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
                <CardTitle>Ventas por día</CardTitle>
            </CardHeader>
            <CardContent>
                <DataTable
                    :columns="columns"
                    :rows="daily"
                    :empty-text="'Sin ventas en el período seleccionado'"
                >
                    <template #date="{ row }">
                        <span class="font-medium tabular-nums">
                            {{ formatDate(row.date) }}
                        </span>
                    </template>
                    <template #count="{ row }">
                        <span class="tabular-nums">{{ row.count }}</span>
                    </template>
                    <template #usd="{ row }">
                        <span class="tabular-nums">
                            {{ formatCoinValue(row.usd, 'USD') }}
                        </span>
                    </template>
                    <template #cop="{ row }">
                        <span class="tabular-nums">
                            {{ formatCoinValue(row.cop, 'COP') }}
                        </span>
                    </template>
                    <template #bs="{ row }">
                        <span class="tabular-nums">
                            {{ formatCoinValue(row.bs, 'BS') }}
                        </span>
                    </template>
                </DataTable>
            </CardContent>
            <CardFooter
                v-if="daily.length > 0"
                class="flex flex-wrap items-center justify-end gap-x-6 gap-y-2 border-t text-sm"
            >
                <span class="font-medium text-foreground">Total</span>
                <span class="text-muted-foreground">
                    {{ formatCoinValue(totals.usd, 'USD') }}
                </span>
                <span class="text-muted-foreground">
                    {{ formatCoinValue(totals.cop, 'COP') }}
                </span>
                <span class="text-muted-foreground">
                    {{ formatCoinValue(totals.bs, 'BS') }}
                </span>
            </CardFooter>
        </Card>
    </div>
</template>
