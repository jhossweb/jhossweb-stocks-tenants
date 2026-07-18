<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ShoppingCart } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

interface Purchase {
    id: number;
    invoice_number: string;
    invoice_date: string;
    invoice_total: string;
    invoice_status: string;
    supplier?: {
        supplier_name: string;
    };
    created_at: string;
}

const props = defineProps<{
    purchases: {
        data: Purchase[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Listado de Compras" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/purchases" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Órdenes de Compra</h1>
                        <p class="text-xs text-zinc-500">Gestiona y registra el ingreso de mercadería al inventario.</p>
                    </div>
                </div>
                <Link href="/purchases/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <ShoppingCart class="w-4 h-4 mr-2" /> Nueva Compra
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Proveedor</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="purchases.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                    <ShoppingCart class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay compras registradas</p>
                                </td>
                            </tr>
                            <tr v-for="purchase in purchases.data" :key="purchase.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ purchase.invoice_number }}</td>
                                <td class="px-4 py-4">{{ purchase.supplier?.supplier_name ?? 'Desconocido' }}</td>
                                <td class="px-4 py-4">{{ formatDate(purchase.invoice_date) }}</td>
                                <td class="px-4 py-4 text-right font-mono">${{ purchase.invoice_total }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-900/30 text-blue-400">
                                        {{ purchase.invoice_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/purchases/${purchase.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Ver Detalles</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="purchases.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in purchases.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
