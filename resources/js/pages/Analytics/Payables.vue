<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { CurrencyMap, PayableDetails } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cuentas por Pagar', href: '/analytics/payables' },
        ],
    },
});

const props = defineProps<{
    summary: {
        balance: Record<string, { balance: number; balance_usd: number }>;
        paid: CurrencyMap;
    };
    details?: PayableDetails;
    selectedMonth: string;
}>();

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

function changeMonth(month: string) {
    router.get(`/analytics/payables`, { month }, { preserveState: true, preserveScroll: true });
}

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

const balanceByCurrency = computed(() => {
    const map: CurrencyMap = {};

    for (const [coin, data] of Object.entries(props.summary.balance)) {
        map[coin as keyof CurrencyMap] = data.balance;
    }

    return map;
});
</script>

<template>
    <Head title="Cuentas por Pagar" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Cuentas por Pagar</h1>
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

        <div class="grid gap-4 md:grid-cols-2">
            <KpiCard
                label="Saldo Pendiente"
                :amounts="balanceByCurrency"
            />
            <KpiCard
                label="Pagado este Mes"
                :amounts="summary.paid"
            />
        </div>

        <Card>
            <CardContent class="p-6">
                <h2 class="mb-4 text-lg font-semibold">Proveedores con Saldo Pendiente</h2>

                <Deferred data="details">
                    <template #fallback>
                        <div class="flex items-center justify-center py-8">
                            <div class="bg-muted-foreground/20 size-6 animate-pulse rounded" />
                        </div>
                    </template>

                    <div
                        v-if="(details?.bySupplier ?? []).length === 0"
                        class="text-muted-foreground py-8 text-center"
                    >
                        No hay cuentas por pagar pendientes
                    </div>

                    <div
                        v-else
                        class="overflow-x-auto"
                    >
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-2 text-left font-medium">Proveedor</th>
                                    <th class="px-4 py-2 text-right font-medium">Moneda</th>
                                    <th class="px-4 py-2 text-right font-medium">Saldo</th>
                                    <th class="px-4 py-2 text-right font-medium">Saldo (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, idx) in details?.bySupplier ?? []"
                                    :key="idx"
                                    class="border-b last:border-0 hover:bg-muted/30"
                                >
                                    <td class="px-4 py-2 font-medium">{{ item.supplier_name }}</td>
                                    <td class="px-4 py-2 text-right">{{ item.invoice_coin }}</td>
                                    <td class="px-4 py-2 text-right font-medium">
                                        $ {{ formatAmount(item.balance) }}
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        $ {{ formatAmount(item.balance_usd) }}
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
