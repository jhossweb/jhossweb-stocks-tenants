<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Undo2 } from 'lucide-vue-next';
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

const props = defineProps<{
    returns: {
        data: PurchaseReturn[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Listado de Devoluciones de Compra" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/purchases" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Devoluciones de Compra</h1>
                        <p class="text-xs text-zinc-500">Registra devoluciones a proveedor y descuenta automáticamente del inventario.</p>
                    </div>
                </div>
                <Link href="/purchase-returns/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <Undo2 class="w-4 h-4 mr-2" /> Nueva Devolución
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Proveedor</th>
                                <th class="px-4 py-4 text-center">Cond. Pago</th>
                                <th class="px-4 py-4 text-center">Días Créd.</th>
                                <th class="px-4 py-4 text-left">Vencimiento</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="returns.data.length === 0">
                                <td colspan="9" class="px-4 py-12 text-center text-zinc-500">
                                    <Undo2 class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay devoluciones registradas</p>
                                </td>
                            </tr>
                            <tr v-for="item in returns.data" :key="item.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ item.invoice_number }}</td>
                                <td class="px-4 py-4">{{ item.supplier?.supplier_name ?? 'Desconocido' }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span :class="item.invoice_payment_condition === 'credit' ? 'text-amber-400' : 'text-zinc-400'">{{ item.invoice_payment_condition === 'credit' ? 'Crédito' : 'Contado' }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span :class="(item.invoice_credit_days ?? 0) > 0 ? 'text-amber-400' : 'text-zinc-500'">{{ item.invoice_credit_days ?? 0 }} días</span>
                                </td>
                                <td class="px-4 py-4">{{ item.invoice_due_date ? formatDate(item.invoice_due_date) : '-' }}</td>
                                <td class="px-4 py-4">{{ formatDate(item.invoice_date) }}</td>
                                <td class="px-4 py-4 text-right font-mono">${{ item.invoice_total }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full bg-orange-900/30 text-orange-400">
                                        {{ item.invoice_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/purchase-returns/${item.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Ver Detalles</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="returns.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in returns.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
