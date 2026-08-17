<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileDown, ShoppingBag } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

const decodeLabel = (label: string) => {
    const textarea = document.createElement('textarea');

    textarea.innerHTML = label;

    return textarea.value;
};

interface Sale {
    id: number;
    sls_number: string;
    sls_date: string;
    sls_total: string;
    sls_status: string;
    sls_payment_condition?: string;
    sls_credit_days?: number;
    sls_due_date?: string;
    customer?: { clt_name: string };
    created_at: string;
}

defineProps<{
    sales: {
        data: Sale[];
        links: any[];
    };
}>();
</script>

<template>
    <Head title="Listado de Ventas" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/dashboard"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Ventas</h1>
                        <p class="text-xs text-zinc-500">
                            Gestiona y registra las ventas realizadas.
                        </p>
                    </div>
                </div>
                <Link
                    href="/sales/create"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <ShoppingBag class="mr-2 h-4 w-4" /> Nueva Venta
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-center">
                                    Cond. Pago
                                </th>
                                <th class="px-4 py-4 text-center">
                                    Días Créd.
                                </th>
                                <th class="px-4 py-4 text-left">Vencimiento</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="sales.data.length === 0">
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <ShoppingBag
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay ventas registradas</p>
                                </td>
                            </tr>
                            <tr
                                v-for="sale in sales.data"
                                :key="sale.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4 font-mono text-xs">
                                    #{{ sale.sls_number }}
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        sale.customer?.clt_name ?? 'Desconocido'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            sale.sls_payment_condition ===
                                            'credit'
                                                ? 'text-amber-400'
                                                : 'text-zinc-400'
                                        "
                                        >{{
                                            sale.sls_payment_condition ===
                                            'credit'
                                                ? 'Crédito'
                                                : 'Contado'
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            (sale.sls_credit_days ?? 0) > 0
                                                ? 'text-amber-400'
                                                : 'text-zinc-500'
                                        "
                                        >{{
                                            sale.sls_credit_days ?? 0
                                        }}
                                        días</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        sale.sls_due_date
                                            ? formatDate(sale.sls_due_date)
                                            : '-'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ formatDate(sale.sls_date) }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono">
                                    ${{ sale.sls_total }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs"
                                        :class="{
                                            'bg-blue-900/30 text-blue-400':
                                                sale.sls_status === 'pending',
                                            'bg-green-900/30 text-green-400':
                                                sale.sls_status === 'paid',
                                            'bg-yellow-900/30 text-yellow-400':
                                                sale.sls_status === 'partial',
                                            'bg-red-900/30 text-red-400':
                                                sale.sls_status === 'cancelled',
                                        }"
                                    >
                                        {{ sale.sls_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex items-center justify-center gap-3"
                                    >
                                        <a
                                            :href="`/sales/${sale.id}/pdf`"
                                            class="text-zinc-400 transition-colors hover:text-zinc-200"
                                            title="Exportar PDF"
                                        >
                                            <FileDown class="h-4 w-4" />
                                        </a>
                                        <Link
                                            :href="`/sales/${sale.id}`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300"
                                            >Ver Detalles</Link
                                        >
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="sales.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template v-for="(link, key) in sales.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                            >{{ decodeLabel(link.label) }}</div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                            >{{ decodeLabel(link.label) }}</Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
