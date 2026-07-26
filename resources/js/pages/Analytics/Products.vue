<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3';
import { AlertTriangle, Package } from 'lucide-vue-next';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import type { ProductDetails, LowStockItem } from '@/types/Dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Ventas', href: '/dashboard/ventas' },
            { title: 'Analítica de Productos', href: '/analytics/products' },
        ],
    },
});

const props = defineProps<{
    summary: {
        totalSkus: number;
        lowStock: LowStockItem[];
    };
    details?: ProductDetails;
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
    router.get(`/analytics/products`, { month }, { preserveState: true, preserveScroll: true });
}

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
}

function getLowStockCount(): number {
    return props.summary.lowStock.length;
}

function getAllLowStock(): LowStockItem[] {
    if (props.details?.lowStock) {
        return props.details.lowStock;
    }

    return props.summary.lowStock;
}
</script>

<template>
    <Head title="Analítica de Productos" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Analítica de Productos</h1>
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
            <Card>
                <CardContent class="flex flex-col gap-2 p-6">
                    <div class="flex items-center gap-2">
                        <Package class="text-muted-foreground size-4" />
                        <span class="text-sm font-medium">Total SKUs</span>
                    </div>
                    <span class="text-foreground text-2xl font-semibold">{{ formatAmount(summary.totalSkus) }}</span>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="flex flex-col gap-2 p-6">
                    <div class="flex items-center gap-2">
                        <AlertTriangle
                            v-if="getLowStockCount() > 0"
                            class="size-4 text-amber-500"
                        />
                        <span class="text-sm font-medium">Stock Bajo</span>
                    </div>
                    <span class="text-foreground text-2xl font-semibold">{{ getLowStockCount() }}</span>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardContent class="p-6">
                    <h2 class="mb-4 text-lg font-semibold">Más Vendidos</h2>

                    <Deferred data="details">
                        <template #fallback>
                            <div class="flex items-center justify-center py-8">
                                <div class="bg-muted-foreground/20 size-6 animate-pulse rounded" />
                            </div>
                        </template>

                        <div
                            v-if="(details?.topSelling ?? []).length === 0"
                            class="text-muted-foreground py-8 text-center"
                        >
                            Sin ventas en este período
                        </div>

                        <div
                            v-else
                            class="overflow-x-auto"
                        >
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-4 py-2 text-left font-medium">Producto</th>
                                        <th class="px-4 py-2 text-right font-medium">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, idx) in details?.topSelling ?? []"
                                        :key="idx"
                                        class="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-2 font-medium">{{ item.pro_name }}</td>
                                        <td class="px-4 py-2 text-right font-medium">{{ formatAmount(item.quantity) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="p-6">
                    <h2 class="mb-4 text-lg font-semibold">Menos Vendidos</h2>

                    <Deferred data="details">
                        <template #fallback>
                            <div class="flex items-center justify-center py-8">
                                <div class="bg-muted-foreground/20 size-6 animate-pulse rounded" />
                            </div>
                        </template>

                        <div
                            v-if="(details?.worstSelling ?? []).length === 0"
                            class="text-muted-foreground py-8 text-center"
                        >
                            Sin datos
                        </div>

                        <div
                            v-else
                            class="overflow-x-auto"
                        >
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-4 py-2 text-left font-medium">Producto</th>
                                        <th class="px-4 py-2 text-right font-medium">Cantidad</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, idx) in details?.worstSelling ?? []"
                                        :key="idx"
                                        class="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td class="px-4 py-2 font-medium">{{ item.pro_name }}</td>
                                        <td class="px-4 py-2 text-right font-medium">{{ formatAmount(item.quantity) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Deferred>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="p-6">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                    <AlertTriangle
                        v-if="getLowStockCount() > 0"
                        class="size-4 text-amber-500"
                    />
                    Productos con Stock Bajo
                </h2>

                <div
                    v-if="getAllLowStock().length === 0"
                    class="text-muted-foreground py-8 text-center"
                >
                    No hay productos con stock bajo
                </div>

                <div
                    v-else
                    class="overflow-x-auto"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium">Producto</th>
                                <th class="px-4 py-2 text-right font-medium">Stock Actual</th>
                                <th class="px-4 py-2 text-right font-medium">Stock Mínimo</th>
                                <th class="px-4 py-2 text-right font-medium">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, idx) in getAllLowStock()"
                                :key="idx"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-2 font-medium">{{ item.pro_name }}</td>
                                <td class="px-4 py-2 text-right">{{ formatAmount(item.stk_cant) }}</td>
                                <td class="px-4 py-2 text-right">{{ formatAmount(item.pro_cant_min) }}</td>
                                <td class="px-4 py-2 text-right font-medium text-red-500">
                                    {{ formatAmount(item.stk_cant - item.pro_cant_min) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
