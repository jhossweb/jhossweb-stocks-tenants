<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Undo2, PlusCircle } from 'lucide-vue-next';
import DocumentLifecycleMenu from '@/components/DocumentLifecycleMenu.vue';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { formatDate } from '@/composables/useDate';

const { can } = usePermissions();

const props = defineProps<{
    returns: any;
}>();
</script>

<template>
    <Head title="Devoluciones de Ventas" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/sales"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">
                            Devoluciones de Ventas
                        </h1>
                        <p class="text-xs text-zinc-500">
                            Gestión de notas de crédito y devoluciones
                        </p>
                    </div>
                </div>
                <Link
                    href="/sale-returns/create"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <PlusCircle class="mr-2 h-4 w-4" /> Nueva Devolución
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div v-if="returns.data.length === 0" class="p-12 text-center">
                    <Undo2 class="mx-auto mb-4 h-12 w-12 text-zinc-600" />
                    <p class="text-zinc-500">No hay devoluciones registradas</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">
                                    N° Devolución
                                </th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-left">
                                    Factura Original
                                </th>
                                <th class="px-4 py-4 text-center">
                                    Cond. Pago
                                </th>
                                <th class="px-4 py-4 text-center">
                                    Días Créd.
                                </th>
                                <th class="px-4 py-4 text-left">Vencimiento</th>
                                <th class="px-4 py-4 text-center">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="r in returns.data"
                                :key="r.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td class="px-4 py-4 font-mono text-xs">
                                    {{ r.sls_number }}
                                </td>
                                <td class="px-4 py-4">
                                    {{ r.customer?.clt_name }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-400"
                                >
                                    {{ r.sls_reference }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            r.sls_payment_condition === 'credit'
                                                ? 'text-amber-400'
                                                : 'text-zinc-400'
                                        "
                                        >{{
                                            r.sls_payment_condition === 'credit'
                                                ? 'Crédito'
                                                : 'Contado'
                                        }}</span
                                    >
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            (r.sls_credit_days ?? 0) > 0
                                                ? 'text-amber-400'
                                                : 'text-zinc-500'
                                        "
                                        >{{ r.sls_credit_days ?? 0 }} días</span
                                    >
                                </td>
                                <td class="px-4 py-4">
                                    {{
                                        r.sls_due_date
                                            ? formatDate(r.sls_due_date)
                                            : '-'
                                    }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    {{ formatDate(r.sls_date) }}
                                </td>
                                <td class="px-4 py-4 text-right font-mono">
                                    {{ r.sls_total }} {{ r.sls_coin }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs"
                                        :class="{
                                            'bg-yellow-900/30 text-yellow-400':
                                                r.sls_status === 'pending',
                                            'bg-green-900/30 text-green-400':
                                                r.sls_status === 'paid',
                                            'bg-red-900/30 text-red-400':
                                                r.sls_status === 'cancelled',
                                        }"
                                    >
                                        {{ r.sls_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex flex-wrap items-center justify-center gap-2"
                                    >
                                        <Link
                                            :href="`/sale-returns/${r.id}/edit`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300"
                                            >Editar</Link
                                        >
                                        <DocumentLifecycleMenu
                                            type="sale-returns"
                                            :record="r"
                                            :allow-deactivate="false"
                                            :can-void="
                                                can('sales.returns.void')
                                            "
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
