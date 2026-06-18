<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Undo2, PlusCircle } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

const props = defineProps<{
    returns: any;
}>();
</script>

<template>
    <Head title="Devoluciones de Ventas" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/sales" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Devoluciones de Ventas</h1>
                        <p class="text-xs text-zinc-500">Gestión de notas de crédito y devoluciones</p>
                    </div>
                </div>
                <Link href="/sale-returns/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <PlusCircle class="w-4 h-4 mr-2" /> Nueva Devolución
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div v-if="returns.data.length === 0" class="p-12 text-center">
                    <Undo2 class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                    <p class="text-zinc-500">No hay devoluciones registradas</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">N° Devolución</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-left">Factura Original</th>
                                <th class="px-4 py-4 text-center">Fecha</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="r in returns.data" :key="r.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">{{ r.sls_number }}</td>
                                <td class="px-4 py-4">{{ r.customer?.clt_name }}</td>
                                <td class="px-4 py-4 font-mono text-xs text-zinc-400">{{ r.sls_reference }}</td>
                                <td class="px-4 py-4 text-center">{{ formatDate(r.sls_date) }}</td>
                                <td class="px-4 py-4 text-right font-mono">{{ r.sls_total }} {{ r.sls_coin }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-yellow-900/30 text-yellow-400': r.sls_status === 'pending',
                                            'bg-green-900/30 text-green-400': r.sls_status === 'paid',
                                            'bg-red-900/30 text-red-400': r.sls_status === 'cancelled',
                                        }">
                                        {{ r.sls_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/sale-returns/${r.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Editar</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
