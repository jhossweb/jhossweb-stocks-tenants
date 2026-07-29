<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Filter, PlusCircle, SearchX } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CashClosingRecord } from '@/types/Finance/CashClosing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cierre de Caja', href: '/cash-closing' },
        ],
    },
});

const props = defineProps<{
    history: {
        data: CashClosingRecord[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        date_from: string | null;
        date_to: string | null;
        status: string | null;
    };
}>();

const filterDateFrom = ref(props.filters.date_from ?? '');
const filterDateTo = ref(props.filters.date_to ?? '');
const filterStatus = ref(props.filters.status ?? '');

function applyFilters() {
    router.get('/cash-closing', {
        date_from: filterDateFrom.value || undefined,
        date_to: filterDateTo.value || undefined,
        status: filterStatus.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function clearFilters() {
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterStatus.value = '';
    router.get('/cash-closing', {}, { preserveState: true, preserveScroll: true });
}

function formatAmount(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

const statusBadgeClass = (status: string) => {
    if (status === 'balanced') {
        return 'bg-green-900/30 text-green-400';
    }
    return 'bg-red-900/30 text-red-400';
};
</script>

<template>
    <Head title="Cierre de Caja" />

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-foreground text-xl font-semibold">Cierre de Caja</h1>
            <Button as-child>
                <Link href="/cash-closing/create">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Cierre
                </Link>
            </Button>
        </div>

        <!-- Filtros -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Filter class="h-4 w-4" />
                    Filtros
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-44">
                        <Label for="date_from">Desde</Label>
                        <Input
                            id="date_from"
                            v-model="filterDateFrom"
                            type="date"
                            class="mt-1"
                        />
                    </div>
                    <div class="w-44">
                        <Label for="date_to">Hasta</Label>
                        <Input
                            id="date_to"
                            v-model="filterDateTo"
                            type="date"
                            class="mt-1"
                        />
                    </div>
                    <div class="w-40">
                        <Label for="status">Estado</Label>
                        <select
                            id="status"
                            v-model="filterStatus"
                            class="bg-background border-input mt-1 block w-full rounded-lg border px-3 py-1.5 text-sm"
                            @change="applyFilters"
                        >
                            <option value="">Todos</option>
                            <option value="balanced">Cuadrado</option>
                            <option value="discrepancy">Con Diferencia</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="secondary" size="sm" @click="applyFilters">
                            Filtrar
                        </Button>
                        <Button variant="outline" size="sm" @click="clearFilters">
                            Limpiar
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Historial -->
        <Card>
            <CardContent class="p-0">
                <div v-if="history.data.length === 0" class="flex flex-col items-center gap-2 py-16 text-zinc-500">
                    <SearchX class="h-12 w-12 text-zinc-600" />
                    <p>No hay cierres registrados</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left font-medium">Fecha</th>
                                <th class="px-4 py-3 text-right font-medium">Apertura</th>
                                <th class="px-4 py-3 text-right font-medium">Esperado</th>
                                <th class="px-4 py-3 text-right font-medium">Contado</th>
                                <th class="px-4 py-3 text-right font-medium">Diferencia</th>
                                <th class="px-4 py-3 text-center font-medium">Estado</th>
                                <th class="px-4 py-3 text-left font-medium">Responsable</th>
                                <th class="px-4 py-3 text-center font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="item in history.data"
                                :key="item.id"
                                class="hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-3 font-mono text-xs">{{ item.cc_date }}</td>
                                <td class="px-4 py-3 text-right font-mono text-xs">
                                    {{ formatAmount(item.cc_opening_balance) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs">
                                    {{ formatAmount(item.cc_counted_amount) }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-xs font-semibold">
                                    {{ formatAmount(item.cc_difference) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-xs font-bold"
                                    :class="item.cc_difference === 0
                                        ? 'text-green-400'
                                        : item.cc_difference > 0
                                            ? 'text-yellow-400'
                                            : 'text-red-400'"
                                >
                                    {{ item.cc_difference >= 0 ? '+' : '' }}{{ formatAmount(item.cc_difference) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-block rounded-full px-2 py-0.5 text-[10px] font-medium uppercase"
                                        :class="statusBadgeClass(item.cc_status)"
                                    >
                                        {{ item.cc_status === 'balanced' ? 'Cuadrado' : 'Diferencia' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-zinc-400">
                                    {{ item.closed_by?.name ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Link
                                        :href="`/cash-closing/${item.cc_date}`"
                                        class="text-xs text-indigo-400 hover:text-indigo-300 hover:underline"
                                    >
                                        Ver detalle
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <tfoot v-if="history.data.length > 0" class="border-t border-zinc-700">
                        <tr>
                            <td class="px-4 py-2 text-xs text-zinc-500" colspan="8">
                                Mostrando {{ history.from }}–{{ history.to }} de {{ history.total }} registros
                            </td>
                        </tr>
                    </tfoot>
                </div>

                <!-- Paginación -->
                <div
                    v-if="history.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template v-for="(link, key) in history.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{ 'border-indigo-600 bg-indigo-600 font-bold text-white': link.active }"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
