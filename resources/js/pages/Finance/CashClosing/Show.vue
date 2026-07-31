<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, StickyNote } from 'lucide-vue-next';
import { computed } from 'vue';
import CashClosingCountTable from '@/components/CashClosing/CashClosingCountTable.vue';
import CashClosingStatusBadge from '@/components/CashClosing/CashClosingStatusBadge.vue';
import CashClosingSummary from '@/components/CashClosing/CashClosingSummary.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    currencyLabels,
    formatAmount,
    formatCoinValue,
    normalizeDifferences,
} from '@/composables/Finance/useCoinFormat';
import type {
    CashClosingRecord,
    GroupedReceipts,
    ReceiptDetail,
} from '@/types/Finance/CashClosing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cierre de Caja', href: '/cash-closing' },
            { title: 'Detalle', href: '' },
        ],
    },
});

const props = defineProps<{
    closing: CashClosingRecord;
    breakdown?: {
        receipts_details: ReceiptDetail[];
        grouped_receipts: GroupedReceipts;
    };
}>();

const normalizedDifferences = computed(() => {
    return normalizeDifferences(props.closing.cc_differences);
});

const coins = computed(() => Object.keys(normalizedDifferences.value));

const hasCountedDetails = computed(() => {
    return (
        props.closing.cc_counted_details &&
        props.closing.cc_counted_details.length > 0
    );
});

const expectedByCoin = computed(() => {
    const expected: Record<string, number> = {};

    for (const coin of coins.value) {
        expected[coin] = expectedFor(coin);
    }

    return expected;
});

function expectedFor(coin: string): number {
    return Object.values(props.breakdown?.grouped_receipts[coin] ?? {}).reduce(
        (sum, value) => sum + value,
        0,
    );
}

function countedFor(coin: string): number {
    return (props.closing.cc_counted_details ?? [])
        .filter((detail) => detail.coin === coin)
        .reduce((sum, detail) => sum + detail.counted, 0);
}

function diffToneClass(coin: string): string {
    const diff = normalizedDifferences.value[coin] ?? 0;

    if (diff === 0) {
        return 'text-zinc-100';
    }

    return diff > 0 ? 'text-yellow-400' : 'text-red-400';
}
</script>

<template>
    <Head :title="'Cierre de Caja - ' + closing.cc_date" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex items-center gap-4">
            <Button variant="outline" size="icon" as-child>
                <Link href="/cash-closing">
                    <ArrowLeft class="size-4" aria-hidden="true" />
                </Link>
            </Button>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-foreground">
                    Cierre de Caja — {{ closing.cc_date }}
                </h1>
                <CashClosingStatusBadge :status="closing.cc_status" />
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Resumen del cierre</CardTitle>
            </CardHeader>
            <CardContent>
                <CashClosingSummary
                    :opening-balance="closing.cc_opening_balance"
                    :total-sales="closing.cc_total_sales"
                    :total-received="closing.cc_total_received"
                    :inflows="closing.cc_total_manual_inflows"
                    :outflows="closing.cc_total_manual_outflows"
                />
            </CardContent>
        </Card>

        <Card v-if="hasCountedDetails">
            <CardHeader>
                <CardTitle>Conteo físico</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    class="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    role="status"
                    aria-live="polite"
                >
                    <div
                        v-for="coin in coins"
                        :key="coin"
                        class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-4"
                    >
                        <p
                            class="text-xs font-semibold text-zinc-500 uppercase"
                        >
                            Total {{ currencyLabels[coin] ?? coin }}
                        </p>
                        <p
                            class="mt-1 font-mono text-xl font-bold text-green-400 tabular-nums"
                        >
                            {{ formatCoinValue(expectedFor(coin), coin) }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-400">
                            Contado:
                            <span
                                class="font-mono font-semibold tabular-nums"
                                :class="diffToneClass(coin)"
                            >
                                {{ formatCoinValue(countedFor(coin), coin) }}
                            </span>
                        </p>
                    </div>
                </div>

                <CashClosingCountTable
                    :rows="closing.cc_counted_details ?? []"
                    readonly
                    :grouped-receipts="breakdown?.grouped_receipts ?? {}"
                    :expected-by-coin="expectedByCoin"
                    :differences-by-coin="normalizedDifferences"
                />
            </CardContent>
        </Card>

        <Card v-if="closing.cc_notes" class="border-zinc-800">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <StickyNote class="size-4" aria-hidden="true" />
                    Notas
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-sm leading-relaxed text-zinc-300">
                    {{ closing.cc_notes }}
                </p>
            </CardContent>
        </Card>

        <Card v-if="closing.movements.length > 0">
            <CardHeader>
                <CardTitle>Movimientos manuales</CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Monto
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Descripción
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Referencia
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="mv in closing.movements"
                                :key="mv.id"
                                class="transition-colors hover:bg-zinc-800/20"
                            >
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase"
                                        :class="
                                            mv.cm_type === 'inflow'
                                                ? 'bg-green-900/30 text-green-400'
                                                : 'bg-red-900/30 text-red-400'
                                        "
                                    >
                                        {{
                                            mv.cm_type === 'inflow'
                                                ? 'Ingreso'
                                                : 'Egreso'
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-base font-semibold tabular-nums"
                                    :class="
                                        mv.cm_type === 'inflow'
                                            ? 'text-green-400'
                                            : 'text-red-400'
                                    "
                                >
                                    {{ mv.cm_type === 'inflow' ? '+ ' : '- '
                                    }}{{ formatAmount(mv.cm_amount) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-300">
                                    {{ mv.cm_description }}
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-500">
                                    {{ mv.cm_reference ?? '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <div
            class="flex flex-wrap gap-x-6 gap-y-2 border-t border-zinc-800 pt-4 text-sm text-zinc-500"
        >
            <span>
                Cerrado por:
                <span class="font-medium text-zinc-300">
                    {{ closing.closed_by?.name ?? '-' }}
                </span>
            </span>
            <span>
                Cerrado el:
                <span class="font-medium text-zinc-300">
                    {{ closing.cc_closed_at }}
                </span>
            </span>
        </div>
    </div>
</template>
