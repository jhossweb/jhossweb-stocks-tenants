<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3';
import { Search, ShoppingCart, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import DashboardHeader from '@/components/Dashboard/DashboardHeader.vue';
import KpiCard from '@/components/Dashboard/KpiCard.vue';
import StatCard from '@/components/Dashboard/StatCard.vue';
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
            {
                title: 'Compras por Proveedor',
                href: '/analytics/purchases-supplier',
            },
        ],
    },
});

const props = defineProps<{
    summary: {
        total: CurrencyMap;
        trend: TrendData;
        supplierCount: number;
    };
    details?: PurchaseDetails;
    selectedMonth: string;
}>();

const { monthOptions, changeMonth } = useDashboardMonth(
    props.selectedMonth,
    '/analytics/purchases-supplier',
);

const filter = ref('');

const filteredSuppliers = computed(() => {
    const items = props.details?.bySupplier ?? [];

    if (!filter.value) {
        return items;
    }

    return items.filter((item) =>
        item.supplier_name.toLowerCase().includes(filter.value.toLowerCase()),
    );
});

const columns: DataTableColumn[] = [
    { key: 'supplier_name', label: 'Proveedor' },
    { key: 'invoice_coin', label: 'Moneda', align: 'right' },
    { key: 'total', label: 'Total', align: 'right', class: 'tabular-nums' },
];
</script>

<template>
    <Head title="Compras por Proveedor" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <DashboardHeader
            title="Compras por Proveedor"
            description="Desglose de compras por proveedor en el período seleccionado"
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
            <StatCard
                label="Proveedores Activos"
                :value="summary.supplierCount"
                :icon="Users"
            />
        </div>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <CardTitle>Desglose por Proveedor</CardTitle>
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filter"
                            type="text"
                            placeholder="Buscar proveedor..."
                            aria-label="Buscar proveedor"
                            class="h-8 w-56 rounded-lg border border-input bg-background py-1.5 pr-3 pl-8 text-sm text-foreground shadow-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        />
                    </div>
                </div>
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
                        :rows="filteredSuppliers"
                        :empty-text="
                            details?.bySupplier?.length
                                ? 'Ningún proveedor coincide con el filtro'
                                : 'Sin compras registradas en ' +
                                  monthLabel(selectedMonth)
                        "
                    >
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
