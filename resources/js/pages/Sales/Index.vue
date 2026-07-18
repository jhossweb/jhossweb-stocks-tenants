<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ShoppingBag } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

interface Sale {
    id: number;
    sls_number: string;
    sls_date: string;
    sls_total: string;
    sls_status: string;
    customer?: { clt_name: string; };
    created_at: string;
}

const props = defineProps<{
    sales: {
        data: Sale[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Listado de Ventas" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/dashboard" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Ventas</h1>
                        <p class="text-xs text-zinc-500">Gestiona y registra las ventas realizadas.</p>
                    </div>
                </div>
                <Link href="/sales/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <ShoppingBag class="w-4 h-4 mr-2" /> Nueva Venta
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="sales.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                    <ShoppingBag class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay ventas registradas</p>
                                </td>
                            </tr>
                            <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ sale.sls_number }}</td>
                                <td class="px-4 py-4">{{ sale.customer?.clt_name ?? 'Desconocido' }}</td>
                                <td class="px-4 py-4">{{ formatDate(sale.sls_date) }}</td>
                                <td class="px-4 py-4 text-right font-mono">${{ sale.sls_total }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-blue-900/30 text-blue-400': sale.sls_status === 'pending',
                                            'bg-green-900/30 text-green-400': sale.sls_status === 'paid',
                                            'bg-yellow-900/30 text-yellow-400': sale.sls_status === 'partial',
                                            'bg-red-900/30 text-red-400': sale.sls_status === 'cancelled',
                                        }">
                                        {{ sale.sls_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/sales/${sale.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Ver Detalles</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="sales.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in sales.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
