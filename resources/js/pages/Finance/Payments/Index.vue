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
import type { AccountPaymentEntry } from '@/types/Finance/PaymentHistory';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Finanzas', href: '/dashboard/finanzas' },
            { title: 'Pagos', href: '/payments' },
        ],
    },
});

const props = defineProps<{
    payments: {
        data: AccountPaymentEntry[];
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
            '/payments',
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

    return { label: 'Pago', class: 'bg-blue-900/30 text-blue-400' };
};
</script>

<template>
    <Head title="Pagos a Proveedores" />

    <div
        class="flex flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6"
    >
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-foreground">
                    Pagos a Proveedores
                </h1>
                <p class="mt-0.5 text-sm text-zinc-500">
                    Historial de pagos realizados a los proveedores.
                </p>
            </div>
            <Button size="lg" as-child>
                <Link href="/payments/create">
                    <PlusCircle class="mr-2 size-5" aria-hidden="true" />
                    Nuevo Pago
                </Link>
            </Button>
        </div>

        <div class="flex items-center gap-4">
            <Input
                v-model="search"
                type="search"
                placeholder="Buscar por factura o proveedor..."
                class="h-10 max-w-sm"
            />
        </div>

        <Card>
            <CardContent class="p-0">
                <div
                    v-if="payments.data.length === 0"
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
                            No hay pagos registrados
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Registra un pago para verlo aquí.
                        </p>
                    </div>
                    <Button as-child>
                        <Link href="/payments/create">
                            <PlusCircle
                                class="mr-2 size-4"
                                aria-hidden="true"
                            />
                            Nuevo Pago
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
                                    Proveedor
                                </th>
                                <th
                                    class="px-4 py-3 text-center text-xs font-semibold text-zinc-400"
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
                                v-for="payment in payments.data"
                                :key="payment.id"
                                class="transition-colors hover:bg-zinc-800/20"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-sm text-zinc-300"
                                >
                                    {{
                                        formatDate(
                                            payment.bank_transaction
                                                ?.btr_transaction_date ??
                                                payment.created_at,
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-300"
                                >
                                    #{{
                                        payment.account_payable?.purchase_order
                                            ?.invoice_number ?? '—'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-zinc-200">
                                    {{
                                        payment.account_payable?.purchase_order
                                            ?.supplier?.supplier_name ??
                                        'Desconocido'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Badge
                                        variant="outline"
                                        :class="
                                            typeBadge(payment.apy_type).class
                                        "
                                    >
                                        {{ typeBadge(payment.apy_type).label }}
                                    </Badge>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-base tabular-nums"
                                >
                                    <span class="text-zinc-100">
                                        {{
                                            formatCoinValue(
                                                Number(
                                                    payment.bank_transaction
                                                        ?.btr_amount ??
                                                        payment.apy_amount,
                                                ),
                                                payment.bank_transaction
                                                    ?.btr_coin ?? 'USD',
                                            )
                                        }}
                                    </span>
                                    <span class="ml-1 text-xs text-zinc-500">
                                        {{
                                            payment.bank_transaction
                                                ?.btr_coin ?? 'USD'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-zinc-400">
                                    {{
                                        payment.bank_transaction?.bank_account
                                            ?.bank_name ?? '—'
                                    }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-400"
                                >
                                    {{
                                        payment.bank_transaction
                                            ?.btr_reference ?? '—'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        as-child
                                    >
                                        <Link :href="`/payments/${payment.id}`">
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
                        v-if="payments.data.length > 0"
                        class="border-t border-zinc-700"
                    >
                        <tr>
                            <td
                                class="px-4 py-3 text-xs text-zinc-500"
                                colspan="8"
                            >
                                Mostrando {{ payments.from }}–{{
                                    payments.to
                                }}
                                de {{ payments.total }} registros
                            </td>
                        </tr>
                    </tfoot>
                </div>

                <div
                    v-if="payments.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1.5">
                        <template
                            v-for="(link, key) in payments.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                            >
                                {{ decodeLabel(link.label) }}
                            </div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-primary bg-primary font-bold text-white':
                                        link.active,
                                }"
                                >{{ decodeLabel(link.label) }}</Link
                            >
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
