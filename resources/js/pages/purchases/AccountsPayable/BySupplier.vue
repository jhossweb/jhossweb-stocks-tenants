<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    Download,
    Search,
    Truck,
    X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { useAging } from '@/composables/useAging';
import { formatDate } from '@/composables/useDate';
import accountsPayableRoutes from '@/routes/accounts-payable';
import type { SupplierBalanceSummary } from '@/types/Purchases';

const props = defineProps<{
    summaries: SupplierBalanceSummary[];
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');

let timeoutId: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            accountsPayableRoutes.bySupplier.url({
                query: { search: search.value || null },
            }),
            {},
            {
                preserveState: true,
                replace: true,
            },
        );
    }, 300);
});

const clearFilters = () => {
    search.value = '';
};

const { agingRows, dayBadge } = useAging();

const expanded = ref<Set<string>>(new Set());

const toggle = (key: string) => {
    const next = new Set(expanded.value);

    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }

    expanded.value = next;
};

const totalsByCoin = computed(() => {
    const totals: Record<string, number> = {};

    for (const summary of props.summaries) {
        totals[summary.coin] =
            (totals[summary.coin] ?? 0) + summary.total_balance;
    }

    return totals;
});

const statementUrl = (summary: SupplierBalanceSummary) =>
    summary.supplier.id
        ? accountsPayableRoutes.statement.url({ supplier: summary.supplier.id })
        : undefined;

const rowKey = (summary: SupplierBalanceSummary) =>
    `${summary.supplier.id}-${summary.coin}`;
</script>

<template>
    <Head title="CxP por Proveedor - Resumen de Pagos" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/accounts-payable"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">
                            Cuentas por Pagar · Por Proveedor
                        </h1>
                        <p class="text-xs text-zinc-500">
                            Saldo consolidado por proveedor y moneda, con
                            antigüedad de deuda.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="summaries.length > 0"
                class="flex flex-wrap items-center gap-3"
            >
                <div
                    v-for="(total, coin) in totalsByCoin"
                    :key="coin"
                    class="rounded-xl border border-zinc-800 bg-zinc-900 px-5 py-3"
                >
                    <div
                        class="text-[10px] font-bold tracking-wider text-zinc-500 uppercase"
                    >
                        {{ coin }}
                    </div>
                    <div class="font-mono text-lg font-bold text-zinc-100">
                        {{ total.toFixed(4) }}
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
            >
                <div
                    class="flex flex-col items-stretch justify-between gap-4 md:flex-row md:items-center"
                >
                    <div class="relative max-w-sm flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="search"
                            placeholder="Buscar por proveedor o factura..."
                            class="border-zinc-700 bg-zinc-800 pl-10 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>

                    <button
                        v-if="search"
                        @click="clearFilters"
                        class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-red-400"
                    >
                        <X class="h-4 w-4" />
                        Limpiar Filtros
                    </button>
                </div>
            </div>

            <div
                v-if="summaries.length === 0"
                class="rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="px-4 py-12 text-center text-zinc-500">
                    <Truck class="mx-auto mb-4 h-12 w-12 text-zinc-600" />
                    <p>No hay saldos pendientes por proveedor</p>
                </div>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="summary in summaries"
                    :key="rowKey(summary)"
                    class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
                >
                    <button
                        class="flex w-full flex-col gap-4 px-5 py-4 text-left transition-colors hover:bg-zinc-800/30 lg:flex-row lg:items-center"
                        @click="toggle(rowKey(summary))"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span
                                    class="truncate font-semibold text-zinc-100"
                                    >{{ summary.supplier.supplier_name }}</span
                                >
                                <span
                                    class="rounded-full bg-indigo-900/40 px-2 py-0.5 text-[10px] font-bold text-indigo-300"
                                >
                                    {{ summary.coin }}
                                </span>
                            </div>
                            <div class="mt-0.5 text-[11px] text-zinc-500">
                                <span class="mr-3"
                                    >{{
                                        summary.documents_count
                                    }}
                                    documento(s)</span
                                >
                                <span v-if="summary.oldest_due_date"
                                    >Más antiguo:
                                    {{
                                        formatDate(summary.oldest_due_date)
                                    }}</span
                                >
                            </div>
                        </div>

                        <div class="hidden items-center gap-4 xl:flex">
                            <div
                                v-for="bucket in agingRows(summary.aging)"
                                :key="bucket.key"
                                class="min-w-16 text-center"
                                :class="bucket.value === 0 ? 'opacity-30' : ''"
                            >
                                <div
                                    class="text-[9px] font-bold tracking-wider text-zinc-500 uppercase"
                                >
                                    {{ bucket.label }}
                                </div>
                                <div
                                    class="font-mono text-xs font-semibold"
                                    :class="bucket.class"
                                >
                                    {{ bucket.value.toFixed(2) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-4">
                            <ChevronDown
                                class="h-5 w-5 text-zinc-500 transition-transform"
                                :class="
                                    expanded.has(rowKey(summary))
                                        ? 'rotate-180'
                                        : ''
                                "
                            />
                        </div>
                    </button>

                    <div
                        v-if="expanded.has(rowKey(summary))"
                        class="border-t border-zinc-800 bg-zinc-950/60"
                    >
                        <div class="space-y-4 px-5 py-4">
                            <div
                                class="grid grid-cols-2 gap-3 sm:grid-cols-5 xl:hidden"
                            >
                                <div
                                    v-for="bucket in agingRows(summary.aging)"
                                    :key="bucket.key"
                                    class="rounded-lg border border-zinc-800 bg-zinc-900 px-3 py-2"
                                    :class="
                                        bucket.value === 0 ? 'opacity-30' : ''
                                    "
                                >
                                    <div
                                        class="text-[9px] font-bold tracking-wider text-zinc-500 uppercase"
                                    >
                                        {{ bucket.label }}
                                    </div>
                                    <div
                                        class="font-mono text-sm font-semibold"
                                        :class="bucket.class"
                                    >
                                        {{ bucket.value.toFixed(2) }}
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead
                                        class="border-b border-zinc-800 text-[10px] font-bold text-zinc-500 uppercase"
                                    >
                                        <tr>
                                            <th class="px-3 py-2 text-left">
                                                Factura
                                            </th>
                                            <th class="px-3 py-2 text-left">
                                                Fecha
                                            </th>
                                            <th class="px-3 py-2 text-left">
                                                Vencimiento
                                            </th>
                                            <th class="px-3 py-2 text-center">
                                                Estado
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Total
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Abonado
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Saldo
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-800">
                                        <tr
                                            v-for="detail in summary.details"
                                            :key="detail.id"
                                            class="hover:bg-zinc-800/30"
                                        >
                                            <td
                                                class="px-3 py-2.5 font-mono text-xs"
                                            >
                                                #{{ detail.number }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 text-zinc-400"
                                            >
                                                {{ formatDate(detail.date) }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 text-zinc-400"
                                            >
                                                {{
                                                    formatDate(detail.due_date)
                                                }}
                                            </td>
                                            <td class="px-3 py-2.5 text-center">
                                                <span
                                                    class="inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap"
                                                    :class="
                                                        dayBadge(
                                                            detail.days_remaining,
                                                        ).class
                                                    "
                                                >
                                                    {{
                                                        dayBadge(
                                                            detail.days_remaining,
                                                        ).label
                                                    }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-3 py-2.5 text-right font-mono text-zinc-300"
                                            >
                                                {{ detail.total.toFixed(4) }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 text-right font-mono text-emerald-400"
                                            >
                                                {{ detail.paid.toFixed(4) }}
                                            </td>
                                            <td
                                                class="px-3 py-2.5 text-right font-mono text-red-400"
                                            >
                                                {{ detail.balance.toFixed(4) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-if="statementUrl(summary)"
                                class="flex justify-end"
                            >
                                <a
                                    :href="statementUrl(summary)"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 bg-zinc-800 px-4 py-2 text-xs font-semibold text-zinc-200 transition-colors hover:bg-zinc-700"
                                >
                                    <Download class="h-3.5 w-3.5" /> Estado de
                                    Cuenta PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
