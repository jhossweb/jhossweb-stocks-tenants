<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList } from 'lucide-vue-next';
import { formatDate } from '@/composables/useDate';

interface Adjustment {
    id: number;
    inv_number: string;
    inv_date: string;
    inv_notes: string;
    inv_status: string;
    created_at: string;
    movements: any[];
    transactionType?: { trans_type_code: string; trans_type_description: string };
}

const props = defineProps<{
    adjustments: {
        data: Adjustment[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Ajustes de Inventario" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/inventory" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Ajustes de Inventario</h1>
                        <p class="text-xs text-zinc-500">Gestión de ajustes por merma, deterioro y vencido.</p>
                    </div>
                </div>
                <Link href="/inventory/adjustments/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <ClipboardList class="w-4 h-4 mr-2" /> Nuevo Ajuste
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">N° Ajuste</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Notas</th>
                                <th class="px-4 py-4 text-center">Items</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="adjustments.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                    <ClipboardList class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay ajustes registrados</p>
                                </td>
                            </tr>
                            <tr v-for="adj in adjustments.data" :key="adj.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ adj.inv_number }}</td>
                                <td class="px-4 py-4">{{ formatDate(adj.inv_date) }}</td>
                                <td class="px-4 py-4 max-w-[200px] truncate text-zinc-400">{{ adj.inv_notes || '-' }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full bg-zinc-800 text-zinc-300">
                                        {{ adj.movements?.length || 0 }} productos
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full bg-green-900/30 text-green-400">
                                        {{ adj.inv_status === 'completed' ? 'Completado' : adj.inv_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/inventory/adjustments/${adj.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Ver Detalles</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="adjustments.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in adjustments.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
