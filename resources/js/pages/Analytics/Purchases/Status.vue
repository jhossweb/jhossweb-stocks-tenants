<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { CurrencyMap, TrendData, PurchaseDetails } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Compras', href: '/dashboard/compras' },
            { title: 'Compras por Estado', href: '/analytics/purchases-status' },
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

const statusFilter = ref('all');

const filteredByStatus = computed(() => {
    const items = props.details?.byStatus ?? [];

    if (statusFilter.value === 'all') return items;

    return items.filter((i) => i.invoice_status === statusFilter.value);
});

const monthOptions = computed(() => {
    const options: { value: string; label: string }[] = [];
    const now = new Date();

    for (let i = 0; i < 12; i++) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        const label = d.toLocaleDateString('es', { year: 'numeric', month: 'long' });
        options.push({ value, label });
    }

    return options;
});

const selectedMonthLabel = computed(
    () => monthOptions.value.find((o) => o.value === props.selectedMonth)?.label ?? props.selectedMonth,
);

function changeMonth(month: string) {
    router.get('/analytics/purchases-status', { month }, { preserveState: true, preserveScroll: true });
}

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) return '-';

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

function currencySymbol(currency: string): string {
    const symbols: Record<string, string> = {
        USD: '$',
        COP: '$',
        BS: 'Bs',
        EUR: '€',
    };

    return symbols[currency] ?? currency;
}

function statusBadge(status: string): string {
    const map: Record<string, string> = {
        paid: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pending: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    return map[status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
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

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Compras por Estado</h1>
            <select
                :value="selectedMonth"
                class="bg-background border-input rounded-lg border px-3 py-1.5 text-sm"
                @change="changeMonth(($event.target as HTMLSelectElement).value)"
            >
                <option
                    v-for="opt in monthOptions"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </select>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <KpiCard
                label="Total Compras"
                :amounts="summary.total"
                :trend="summary.trend"
                variant="success"
            />
            <KpiCard
                label="Pendiente"
                :amounts="summary.pending"
                variant="danger"
            />
        </div>

        <Card>
            <CardContent class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Desglose por Estado</h2>
                    <select
                        v-model="statusFilter"
                        class="bg-background border-input rounded-lg border px-3 py-1.5 text-sm"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="paid">Pagado</option>
                        <option value="partial">Parcial</option>
                        <option value="pending">Pendiente</option>
                    </select>
                </div>

                <Deferred data="details">
                    <template #fallback>
                        <div class="flex items-center justify-center py-8">
                            <div class="bg-muted-foreground/20 size-6 animate-pulse rounded" />
                        </div>
                    </template>

                    <div
                        v-if="filteredByStatus.length === 0"
                        class="text-muted-foreground py-8 text-center"
                    >
                        {{ details?.byStatus?.length ? 'Ninguna compra coincide con el filtro' : 'Sin compras registradas en ' + selectedMonthLabel }}
                    </div>

                    <div
                        v-else
                        class="overflow-x-auto"
                    >
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-2 text-left font-medium">Estado</th>
                                    <th class="px-4 py-2 text-right font-medium">Moneda</th>
                                    <th class="px-4 py-2 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, idx) in filteredByStatus"
                                    :key="idx"
                                    class="border-b last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-4 py-2">
                                        <span
                                            class="inline-block rounded-full px-2 py-0.5 text-xs font-medium uppercase"
                                            :class="statusBadge(item.invoice_status)"
                                        >
                                            {{ statusLabel(item.invoice_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right">{{ item.invoice_coin }}</td>
                                    <td class="px-4 py-2 text-right font-medium">
                                        {{ currencySymbol(item.invoice_coin) }} {{ formatAmount(item.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Deferred>
            </CardContent>
        </Card>
    </div>
</template>