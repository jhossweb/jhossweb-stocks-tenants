<script setup lang="ts">
import { History, Loader2, PackageX, RefreshCw } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    formatDate,
    formatPercent,
    formatUsd,
} from '@/composables/Inventory/useProductPrices';
import type { PriceHistoryResponse } from '@/types/Inventory';

const props = defineProps<{
    productCode: string | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const data = ref<PriceHistoryResponse | null>(null);
const loading = ref(false);
const error = ref(false);

const load = async () => {
    if (!props.productCode) {
        return;
    }

    loading.value = true;
    error.value = false;

    try {
        const res = await fetch(
            `/api/products/${encodeURIComponent(props.productCode)}/price-history`,
        );

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        data.value = await res.json();
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
};

watch([open, () => props.productCode], ([isOpen]) => {
    if (
        isOpen &&
        props.productCode &&
        data.value?.product.pro_code !== props.productCode
    ) {
        load();
    }
});

watch(
    () => props.productCode,
    () => {
        data.value = null;
    },
);

interface Point {
    x: number;
    y: number;
    value: number;
}

const W = 600;
const H = 170;
const PAD_X = 10;
const PAD_Y = 14;

/** Serie cronológica ascendente de un tipo de entrada. */
const seriesOf = (type: 'precio' | 'costo') =>
    [...(data.value?.history ?? [])].filter((e) => e.type === type).reverse();

const scalePoints = (
    entries: { amount: number }[],
    min: number,
    span: number,
): Point[] =>
    entries.map((entry, i) => ({
        x:
            entries.length === 1
                ? W / 2
                : PAD_X + (i * (W - PAD_X * 2)) / (entries.length - 1),
        y: H - PAD_Y - ((entry.amount - min) / span) * (H - PAD_Y * 2),
        value: entry.amount,
    }));

const sparkline = computed(() => {
    const prices = seriesOf('precio');
    const costs = seriesOf('costo');

    if (prices.length === 0 && costs.length === 0) {
        return null;
    }

    const values = [...prices, ...costs].map((e) => e.amount);
    const rawMin = Math.min(...values);
    const rawMax = Math.max(...values);
    const pad = (rawMax - rawMin || rawMax || 1) * 0.12;

    const min = Math.max(0, rawMin - pad);
    const span = rawMax - rawMin + pad * 2;

    return {
        prices: scalePoints(prices, min, span),
        costs: scalePoints(costs, min, span),
        firstDate: data.value?.history.at(-1)?.date ?? null,
        lastDate: data.value?.history[0]?.date ?? null,
    };
});

const polyline = (points: Point[]) =>
    points.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');

const lastPriceEntry = computed(() => seriesOf('precio').at(-1));
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent
            side="right"
            class="w-full overflow-y-auto border-zinc-800 bg-zinc-950 p-0 text-zinc-100 sm:max-w-md"
        >
            <div
                class="sticky top-0 border-b border-zinc-800 bg-zinc-950/95 backdrop-blur"
            >
                <SheetHeader class="p-5 pb-4">
                    <SheetTitle class="text-base font-bold text-zinc-100">
                        {{ data?.product.pro_name ?? 'Histórico' }}
                    </SheetTitle>
                    <SheetDescription
                        class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-zinc-500"
                    >
                        <span class="font-mono">{{
                            data?.product.pro_code
                        }}</span>
                        <span v-if="data?.product.category"
                            >· {{ data.product.category }}</span
                        >
                        <span v-if="data?.product.brand"
                            >· {{ data.product.brand }}</span
                        >
                    </SheetDescription>
                </SheetHeader>

                <div
                    v-if="lastPriceEntry"
                    class="flex items-end justify-between px-5 pb-4"
                >
                    <div>
                        <p
                            class="text-[10px] tracking-wider text-zinc-500 uppercase"
                        >
                            Precio vigente
                        </p>
                        <p
                            class="font-mono text-2xl font-bold text-indigo-400 tabular-nums"
                        >
                            {{ formatUsd(lastPriceEntry.amount) }}
                        </p>
                        <p
                            v-if="lastPriceEntry.utility !== null"
                            class="text-[10px] text-zinc-500"
                        >
                            utilidad registrada
                            {{ formatPercent(lastPriceEntry.utility) }}
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        class="border-zinc-700 text-[10px] text-zinc-400"
                    >
                        {{ formatDate(lastPriceEntry.date) }}
                    </Badge>
                </div>
            </div>

            <div class="space-y-6 p-5">
                <!-- Estado: cargando -->
                <div
                    v-if="loading"
                    class="flex flex-col items-center gap-3 py-16 text-zinc-500"
                >
                    <Loader2 class="h-7 w-7 animate-spin text-indigo-400" />
                    <p class="text-xs">Cargando histórico…</p>
                </div>

                <!-- Estado: error -->
                <div
                    v-else-if="error"
                    class="flex flex-col items-center gap-3 py-16 text-zinc-500"
                >
                    <PackageX class="h-7 w-7 text-red-400" />
                    <p class="text-xs">No se pudo cargar el histórico.</p>
                    <button
                        @click="load"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-700 px-3 py-1.5 text-xs transition-colors hover:bg-zinc-800"
                    >
                        <RefreshCw class="h-3 w-3" /> Reintentar
                    </button>
                </div>

                <template v-else-if="data">
                    <!-- Sparkline -->
                    <div
                        v-if="
                            sparkline &&
                            (sparkline.prices.length > 1 ||
                                sparkline.costs.length > 1)
                        "
                    >
                        <svg
                            :viewBox="`0 0 ${W} ${H}`"
                            class="h-auto w-full"
                            role="img"
                            aria-label="Evolución de precio y costo"
                        >
                            <polyline
                                v-if="sparkline.costs.length > 1"
                                :points="polyline(sparkline.costs)"
                                fill="none"
                                stroke="#f59e0b"
                                stroke-opacity="0.55"
                                stroke-width="1.5"
                                stroke-dasharray="5 4"
                            />
                            <polyline
                                v-if="sparkline.prices.length > 1"
                                :points="polyline(sparkline.prices)"
                                fill="none"
                                stroke="#818cf8"
                                stroke-width="2"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                            <circle
                                v-for="(p, i) in sparkline.prices"
                                :key="`p${i}`"
                                :cx="p.x"
                                :cy="p.y"
                                r="2.5"
                                :fill="
                                    i === sparkline.prices.length - 1
                                        ? '#a5b4fc'
                                        : '#818cf8'
                                "
                            >
                                <title>{{ formatUsd(p.value) }}</title>
                            </circle>
                            <circle
                                v-for="(c, i) in sparkline.costs"
                                :key="`c${i}`"
                                :cx="c.x"
                                :cy="c.y"
                                r="2"
                                fill="#f59e0b"
                            >
                                <title>Costo {{ formatUsd(c.value) }}</title>
                            </circle>
                        </svg>
                        <div
                            class="mt-1 flex justify-between px-1 text-[10px] text-zinc-600"
                        >
                            <span>{{ formatDate(sparkline.firstDate) }}</span>
                            <span class="flex items-center gap-3">
                                <span class="flex items-center gap-1"
                                    ><i
                                        class="inline-block h-0.5 w-3 rounded bg-indigo-400"
                                    />
                                    Precio</span
                                >
                                <span class="flex items-center gap-1"
                                    ><i
                                        class="inline-block h-0.5 w-3 rounded bg-amber-500/60"
                                    />
                                    Costo</span
                                >
                            </span>
                            <span>{{ formatDate(sparkline.lastDate) }}</span>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div>
                        <h3
                            class="mb-3 flex items-center gap-1.5 text-[10px] font-bold tracking-wider text-zinc-500 uppercase"
                        >
                            <History class="h-3 w-3" /> Cambios registrados
                        </h3>

                        <ol
                            class="relative ml-1.5 space-y-4 border-l border-zinc-800"
                        >
                            <li
                                v-for="(entry, i) in data.history"
                                :key="`${entry.type}-${entry.date}-${i}`"
                                class="relative pl-4"
                            >
                                <span
                                    class="absolute top-1.5 -left-[4.5px] h-2 w-2 rounded-full ring-2 ring-zinc-950"
                                    :class="
                                        entry.type === 'precio'
                                            ? 'bg-indigo-400'
                                            : 'bg-amber-500'
                                    "
                                />
                                <div
                                    class="flex items-baseline justify-between gap-2"
                                >
                                    <span
                                        class="font-mono text-sm font-semibold tabular-nums"
                                        :class="
                                            entry.type === 'precio'
                                                ? 'text-indigo-300'
                                                : 'text-amber-400/90'
                                        "
                                    >
                                        {{ formatUsd(entry.amount) }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="shrink-0 text-[9px] tracking-wide uppercase"
                                        :class="
                                            entry.type === 'precio'
                                                ? 'border-indigo-900 text-indigo-400'
                                                : 'border-amber-900 text-amber-500'
                                        "
                                    >
                                        {{ entry.type }}
                                    </Badge>
                                </div>
                                <p class="mt-0.5 text-[11px] text-zinc-500">
                                    {{ formatDate(entry.date)
                                    }}<template v-if="entry.user">
                                        · {{ entry.user }}</template
                                    >
                                    <template
                                        v-if="
                                            entry.type === 'precio' &&
                                            entry.utility !== null
                                        "
                                    >
                                        · utilidad
                                        {{ formatPercent(entry.utility) }}
                                    </template>
                                </p>
                            </li>
                        </ol>
                    </div>

                    <!-- Vacío -->
                    <div
                        v-if="data.history.length === 0"
                        class="py-10 text-center text-xs text-zinc-600"
                    >
                        Sin registros de precio o costo para este producto.
                    </div>
                </template>
            </div>
        </SheetContent>
    </Sheet>
</template>
