<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Undo2 } from 'lucide-vue-next';
import DocumentLifecycleMenu from '@/components/DocumentLifecycleMenu.vue';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { formatDate } from '@/composables/useDate';

interface PurchaseReturn {
    id: number;
    invoice_number: string;
    invoice_date: string;
    invoice_total: string;
    invoice_status: string;
    invoice_payment_condition?: string;
    invoice_credit_days?: number;
    invoice_due_date?: string;
    supplier?: {
        supplier_name: string;
    };
    created_at: string;
}

const { can } = usePermissions();

const props = defineProps<{
    returns: {
        data: PurchaseReturn[];
        links: any[];
    };
}>();
</script>

<template>
    <Head title="Listado de Devoluciones de Compra" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/purchases"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">
                            Devoluciones de Compra
                        </h1>
                        <p class="text-xs text-zinc-500">
                            Registra devoluciones a proveedor y descuenta
                            automáticamente del inventario.
                        </p>
                    </div>
                </div>
                <Link
                    href="/purchase-returns/create"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <Undo2 class="mr-2 h-4 w-4" /> Nueva Devolución
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
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Proveedor</th>
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
                            <tr v-if="returns.data.length === 0">
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <Undo2
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay devoluciones registradas</p>
                                </td>
                            </tr>
                            <tr
                                v-for="item in returns.data"
                                :key="item.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4 font-mono text-xs">
                                    #{{ item.invoice_number }}
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        item.supplier?.supplier_name ??
                                        'Desconocido'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            item.invoice_payment_condition ===
                                            'credit'
                                                ? 'text-amber-400'
                                                : 'text-zinc-400'
                                        "
                                        >{{
                                            item.invoice_payment_condition ===
                                            'credit'
                                                ? 'Crédito'
                                                : 'Contado'
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            (item.invoice_credit_days ?? 0) > 0
                                                ? 'text-amber-400'
                                                : 'text-zinc-500'
                                        "
                                        >{{
                                            item.invoice_credit_days ?? 0
                                        }}
                                        días</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        item.invoice_due_date
                                            ? formatDate(item.invoice_due_date)
                                            : '-'
                                    }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ formatDate(item.invoice_date) }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono">
                                    ${{ item.invoice_total }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded-full bg-orange-900/30 px-2 py-1 text-xs text-orange-400"
                                    >
                                        {{ item.invoice_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex flex-wrap items-center justify-center gap-2"
                                    >
                                        <Link
                                            :href="`/purchase-returns/${item.id}/edit`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300"
                                            >Ver Detalles</Link
                                        >
                                        <DocumentLifecycleMenu
                                            type="purchase-returns"
                                            :record="item"
                                            :allow-deactivate="false"
                                            :can-void="
                                                can('purchases.returns.void')
                                            "
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="returns.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in returns.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                v-html="link.label"
                            ></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
