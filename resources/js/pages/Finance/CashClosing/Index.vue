<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Filter, PlusCircle, SearchX } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import CashClosingStatusBadge from '@/components/CashClosing/CashClosingStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    formatAmount,
    formatCoinValue,
    normalizeDifferences,
    roundToCoinPrecision,
} from '@/composables/Finance/useCoinFormat';
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

const totals = computed(() => {
    const balanced = props.history.data.filter(
        (item) => item.cc_status === 'balanced',
    ).length;

    return {
        total: props.history.data.length,
        balanced,
        discrepancy: props.history.data.length - balanced,
    };
});

function applyFilters() {
    router.get(
        '/cash-closing',
        {
            date_from: filterDateFrom.value || undefined,
            date_to: filterDateTo.value || undefined,
            status: filterStatus.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function clearFilters() {
    filterDateFrom.value = '';
    filterDateTo.value = '';
    filterStatus.value = '';
    router.get(
        '/cash-closing',
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function isNewFormat(item: CashClosingRecord): boolean {
    return (
        Array.isArray(item.cc_counted_details) &&
        item.cc_counted_details.length > 0
    );
}

function countedByCoin(item: CashClosingRecord): Record<string, number> {
    const totalsMap: Record<string, number> = {};

    for (const detail of item.cc_counted_details ?? []) {
        totalsMap[detail.coin] = (totalsMap[detail.coin] ?? 0) + detail.counted;
    }

    return totalsMap;
}

function expectedByCoin(item: CashClosingRecord): Record<string, number> {
    const counted = countedByCoin(item);
    const expected: Record<string, number> = {};
    const coins = new Set([
        ...Object.keys(counted),
        ...Object.keys(item.cc_differences ?? {}),
    ]);

    for (const coin of coins) {
        const c = counted[coin] ?? 0;
        const diff = item.cc_differences?.[coin] ?? 0;
        expected[coin] = roundToCoinPrecision(coin, c - diff);
    }

    return expected;
}

function differenceColorClass(item: CashClosingRecord): string {
    if (isNewFormat(item)) {
        const diffs = Object.values(normalizeDifferences(item.cc_differences));

        if (diffs.length === 0 || diffs.every((diff) => diff === 0)) {
            return 'border-green-900/50 bg-green-900/20 text-green-400';
        }

        if (diffs.some((diff) => diff < 0)) {
            return 'border-red-900/50 bg-red-900/20 text-red-400';
        }

        return 'border-amber-900/50 bg-amber-900/20 text-amber-400';
    }

    const diff = item.cc_difference;

    if (diff === null || diff === 0) {
        return 'border-green-900/50 bg-green-900/20 text-green-400';
    }

    if (diff > 0) {
        return 'border-amber-900/50 bg-amber-900/20 text-amber-400';
    }

    return 'border-red-900/50 bg-red-900/20 text-red-400';
}

function differenceText(item: CashClosingRecord): string {
    if (isNewFormat(item)) {
        const diffs = normalizeDifferences(item.cc_differences);

        if (Object.keys(diffs).length === 0) {
            return '—';
        }

        return Object.entries(diffs)
            .map(
                ([coin, diff]) =>
                    `${diff > 0 ? '+' : ''}${formatCoinValue(diff, coin)}`,
            )
            .join(', ');
    }

    if (item.cc_difference === null) {
        return '—';
    }

    return `${item.cc_difference >= 0 ? '+' : ''}${formatAmount(item.cc_difference)}`;
}
</script>

<template>
    <Head title="Cierre de Caja" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Cierre de Caja
                </h1>
                <p class="mt-0.5 text-sm text-zinc-500">
                    Historial de cierres y conciliación de caja.
                </p>
            </div>
            <Button size="lg" as-child>
                <Link href="/cash-closing/create">
                    <PlusCircle class="mr-2 size-5" aria-hidden="true" />
                    Nuevo Cierre
                </Link>
            </Button>
        </div>

        <div v-if="history.data.length > 0" class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
                <p class="text-xs font-semibold text-zinc-500 uppercase">
                    Registros
                </p>
                <p
                    class="mt-1 font-mono text-3xl font-bold text-zinc-100 tabular-nums"
                >
                    {{ totals.total }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
                <p class="text-xs font-semibold text-zinc-500 uppercase">
                    Cuadrados
                </p>
                <p
                    class="mt-1 font-mono text-3xl font-bold text-green-400 tabular-nums"
                >
                    {{ totals.balanced }}
                </p>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
                <p class="text-xs font-semibold text-zinc-500 uppercase">
                    Con diferencia
                </p>
                <p
                    class="mt-1 font-mono text-3xl font-bold text-amber-400 tabular-nums"
                >
                    {{ totals.discrepancy }}
                </p>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Filter class="size-4" aria-hidden="true" />
                    Filtros
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex flex-wrap items-end gap-4">
                    <div>
                        <Label for="date_from">Desde</Label>
                        <Input
                            id="date_from"
                            v-model="filterDateFrom"
                            type="date"
                            class="mt-1 h-10 w-44"
                        />
                    </div>
                    <div>
                        <Label for="date_to">Hasta</Label>
                        <Input
                            id="date_to"
                            v-model="filterDateTo"
                            type="date"
                            class="mt-1 h-10 w-44"
                        />
                    </div>
                    <div>
                        <Label for="status">Estado</Label>
                        <select
                            id="status"
                            v-model="filterStatus"
                            class="mt-1 block h-10 w-44 rounded-lg border border-input bg-background px-3 text-sm"
                            @change="applyFilters"
                        >
                            <option value="">Todos</option>
                            <option value="balanced">Cuadrado</option>
                            <option value="discrepancy">Con Diferencia</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="secondary"
                            size="lg"
                            @click="applyFilters"
                        >
                            Filtrar
                        </Button>
                        <Button
                            variant="outline"
                            size="lg"
                            @click="clearFilters"
                        >
                            Limpiar
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="history.data.length === 0"
                    class="flex flex-col items-center gap-4 py-20 text-center"
                >
                    <div
                        class="flex size-16 items-center justify-center rounded-full bg-zinc-900"
                    >
                        <SearchX
                            class="size-8 text-zinc-500"
                            aria-hidden="true"
                        />
                    </div>
                    <div>
                        <p class="text-base font-semibold text-zinc-300">
                            No hay cierres registrados
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Crea tu primer cierre de caja para empezar a
                            conciliar.
                        </p>
                    </div>
                    <Button as-child>
                        <Link href="/cash-closing/create">
                            <PlusCircle
                                class="mr-2 size-4"
                                aria-hidden="true"
                            />
                            Nuevo Cierre
                        </Link>
                    </Button>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Fecha
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Apertura
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Esperado
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Contado
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Diferencia
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold text-zinc-400"
                                >
                                    Estado
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Responsable
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold text-zinc-400"
                                >
                                    Acción
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="item in history.data"
                                :key="item.id"
                                class="transition-colors hover:bg-zinc-800/20"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-sm text-zinc-300"
                                >
                                    {{ item.cc_date }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-base tabular-nums"
                                >
                                    {{ formatAmount(item.cc_opening_balance) }}
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-1"
                                    >
                                        <template v-if="isNewFormat(item)">
                                            <Badge
                                                v-for="[
                                                    coin,
                                                    value,
                                                ] in Object.entries(
                                                    expectedByCoin(item),
                                                )"
                                                :key="coin"
                                                variant="outline"
                                                class="font-mono"
                                            >
                                                {{
                                                    formatCoinValue(value, coin)
                                                }}
                                            </Badge>
                                        </template>
                                        <span
                                            v-else
                                            class="font-mono text-base tabular-nums"
                                        >
                                            {{
                                                item.cc_expected_closing ===
                                                null
                                                    ? '—'
                                                    : formatAmount(
                                                          item.cc_expected_closing,
                                                      )
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-1"
                                    >
                                        <template v-if="isNewFormat(item)">
                                            <Badge
                                                v-for="[
                                                    coin,
                                                    value,
                                                ] in Object.entries(
                                                    countedByCoin(item),
                                                )"
                                                :key="coin"
                                                variant="outline"
                                                class="font-mono"
                                            >
                                                {{
                                                    formatCoinValue(value, coin)
                                                }}
                                            </Badge>
                                        </template>
                                        <span
                                            v-else
                                            class="font-mono text-base tabular-nums"
                                        >
                                            {{
                                                item.cc_counted_amount === null
                                                    ? '—'
                                                    : formatAmount(
                                                          item.cc_counted_amount,
                                                      )
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <div
                                        class="flex flex-wrap justify-end gap-1"
                                    >
                                        <Badge
                                            variant="outline"
                                            class="font-mono"
                                            :class="differenceColorClass(item)"
                                        >
                                            {{ differenceText(item) }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <CashClosingStatusBadge
                                        :status="item.cc_status"
                                    />
                                </td>
                                <td class="px-4 py-4 text-sm text-zinc-400">
                                    {{ item.closed_by?.name ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link
                                            :href="`/cash-closing/${item.cc_date}`"
                                        >
                                            Ver detalle
                                            <ArrowRight
                                                class="ml-2 size-4"
                                                aria-hidden="true"
                                            />
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <tfoot
                        v-if="history.data.length > 0"
                        class="border-t border-zinc-700"
                    >
                        <tr>
                            <td
                                class="px-4 py-3 text-xs text-zinc-500"
                                colspan="8"
                            >
                                Mostrando {{ history.from }}–{{ history.to }} de
                                {{ history.total }} registros
                            </td>
                        </tr>
                    </tfoot>
                </div>

                <div
                    v-if="history.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1.5">
                        <template
                            v-for="(link, key) in history.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                            >
                                <span v-html="link.label" />
                            </div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
