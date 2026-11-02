<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, PlusCircle, SearchX } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import { formatDate } from '@/composables/useDate';
import type { ReceiptHistoryGroup } from '@/types/Finance/Receipt';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Cobranza', href: '/receipts' },
        ],
    },
});

const props = defineProps<{
    receipts: {
        data: ReceiptHistoryGroup[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search: string | null;
    };
}>();

const search = ref(props.filters.search ?? '');

let timeoutId: ReturnType<typeof setTimeout>;

const decodeLabel = (label: string) => {
    const textarea = document.createElement('textarea');

    textarea.innerHTML = label;

    return textarea.value;
};

watch(search, () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(
            '/receipts',
            { search: search.value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const typeBadge = (type: string) => {
    if (type === 'RETURN_CREDIT') {
        return {
            label: 'Crédito devolución',
            class: 'bg-orange-900/30 text-orange-400',
        };
    }

    return { label: 'Cobro', class: 'bg-blue-900/30 text-blue-400' };
};
</script>

<template>
    <Head title="Cobranza" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">Cobranza</h1>
                <p class="mt-0.5 text-sm text-zinc-500">
                    Historial de cobros realizados a los clientes.
                </p>
            </div>
            <Button size="lg" as-child>
                <Link href="/receipts/create">
                    <PlusCircle class="mr-2 size-5" aria-hidden="true" />
                    Nueva Cobranza
                </Link>
            </Button>
        </div>

        <div class="flex items-center gap-4">
            <Input
                v-model="search"
                type="search"
                placeholder="Buscar por factura o cliente..."
                class="h-10 max-w-sm"
            />
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="receipts.data.length === 0"
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
                            No hay cobros registrados
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Registra un cobro para verlo aquí.
                        </p>
                    </div>
                    <Button as-child>
                        <Link href="/receipts/create">
                            <PlusCircle
                                class="mr-2 size-4"
                                aria-hidden="true"
                            />
                            Nueva Cobranza
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
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Factura
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Cliente
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold text-zinc-400"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                                >
                                    Monto (USD)
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Banco
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                                >
                                    Referencia
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
                                v-for="receipt in receipts.data"
                                :key="receipt.group_key"
                                class="transition-colors hover:bg-zinc-800/20"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-sm text-zinc-300"
                                >
                                    {{
                                        formatDate(
                                            receipt.created_at,
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-300"
                                >
                                    #{{
                                        receipt.accounts_receivable?.sale_order
                                            ?.sls_number ?? '—'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-zinc-200">
                                    {{
                                        receipt.accounts_receivable?.sale_order
                                            ?.customer?.clt_name ??
                                        'Desconocido'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge
                                        variant="outline"
                                        :class="
                                            typeBadge(receipt.rec_type).class
                                        "
                                    >
                                        {{ typeBadge(receipt.rec_type).label }}
                                    </Badge>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-base tabular-nums"
                                >
                                    <span class="text-zinc-100">
                                        {{
                                            formatCoinValue(
                                                Number(receipt.total_usd),
                                                'USD',
                                            )
                                        }}
                                    </span>
                                    <span class="ml-1 text-xs text-zinc-500">
                                        USD
                                    </span>
                                    <span
                                        v-if="receipt.movements_count > 1"
                                        class="ml-2 text-xs text-zinc-400"
                                    >
                                        ({{ receipt.movements_count }} movimientos)
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-zinc-400">
                                    <span v-if="receipt.banks.length === 0"
                                        >—</span
                                    >
                                    <span v-else-if="receipt.banks.length === 1">
                                        {{ receipt.banks[0] }}
                                    </span>
                                    <span v-else>Varios</span>
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-400"
                                >
                                    <span
                                        v-if="receipt.references.length === 0"
                                        >—</span
                                    >
                                    <span v-else-if="receipt.references.length === 1">
                                        {{ receipt.references[0] }}
                                    </span>
                                    <span v-else>Varios</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link :href="`/receipts/${receipt.id}`">
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
                        v-if="receipts.data.length > 0"
                        class="border-t border-zinc-700"
                    >
                        <tr>
                            <td
                                class="px-4 py-3 text-xs text-zinc-500"
                                colspan="8"
                            >
                                Mostrando {{ receipts.from }}–{{
                                    receipts.to
                                }}
                                de {{ receipts.total }} registros
                            </td>
                        </tr>
                    </tfoot>
                </div>
            </CardContent>
        </Card>

        <nav
            v-if="receipts.links.length > 3"
            class="flex items-center justify-end gap-2"
        >
            <template v-for="(link, index) in receipts.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="inline-flex h-9 items-center rounded-md border px-3 text-sm transition-colors hover:bg-accent"
                    :class="{
                        'bg-accent font-medium': link.active,
                    }"
                >
                    <span v-html="decodeLabel(link.label)" />
                </Link>
                <span
                    v-else
                    class="inline-flex h-9 cursor-not-allowed items-center rounded-md border px-3 text-sm opacity-50"
                >
                    <span v-html="decodeLabel(link.label)" />
                </span>
            </template>
        </nav>
    </div>
</template>
