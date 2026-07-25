<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Package,
    AlertTriangle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { DashboardKpis } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
            {
                title: 'Ventas',
                href: '/dashboard/ventas',
            },
        ],
    },
});

const props = defineProps<{
    kpis: DashboardKpis;
    selectedMonth: string;
}>();

function goTo(path: string) {
    router.get(path, { month: props.selectedMonth }, { preserveScroll: true });
}

function changeMonth(month: string) {
    router.get('/dashboard/ventas', { month }, { preserveState: true, preserveScroll: true });
}

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

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

function getTopProduct(items: { pro_name: string; quantity: number }[]): string {
    if (!items.length) {
        return 'Sin ventas';
    }

    const top = items[0];

    return `${top.pro_name} (${formatAmount(top.quantity)} unds)`;
}

function getLowStockAlert(count: number): string {
    if (count === 0) {
        return 'Sin productos con stock bajo';
    }

    return `${count} producto${count !== 1 ? 's' : ''} con stock bajo`;
}
</script>

<template>
    <Head title="Ventas - Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Ventas</h1>
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
                label="Costo Ventas"
                :amounts="kpis.profitLoss.cogs.total"
                :trend="kpis.profitLoss.cogs.trend"
                variant="danger"
                @click="goTo('/analytics/products')"
            />
            <KpiCard
                label="Ganancia Bruta"
                :amounts="kpis.profitLoss.grossProfit.total"
                :trend="kpis.profitLoss.grossProfit.trend"
                variant="success"
                @click="goTo('/analytics/products')"
            />
            <KpiCard
                label="Ganancia Neta"
                :amounts="kpis.profitLoss.netProfit.total"
                :trend="kpis.profitLoss.netProfit.trend"
                variant="info"
                @click="goTo('/analytics/cash-flow')"
            />
        </div>

        <Card
            class="cursor-pointer transition-shadow hover:shadow-md"
            @click="goTo('/analytics/products')"
        >
            <CardContent class="flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <Package class="text-muted-foreground size-4" />
                    <span class="text-sm font-medium">Productos</span>
                </div>

                <div class="grid gap-2 text-sm md:grid-cols-3">
                    <div>
                        <span class="text-muted-foreground">Total SKUs:</span>
                        <span class="ml-1 font-semibold">{{ formatAmount(kpis.products.totalSkus) }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <AlertTriangle
                            v-if="kpis.products.lowStock.length > 0"
                            class="size-3.5 text-amber-500"
                        />
                        <span class="text-muted-foreground">Stock bajo:</span>
                        <span class="ml-1 font-semibold">{{ getLowStockAlert(kpis.products.lowStock.length) }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Más vendido:</span>
                        <span class="ml-1 font-semibold">{{ getTopProduct(kpis.products.topSelling) }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Menos vendido:</span>
                        <span class="ml-1 font-semibold">{{ getTopProduct(kpis.products.worstSelling) }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>