<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, FileText, PackageSearch, ClipboardList } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '#' },
            { title: 'Transacciones', href: '#' },
            { title: 'Ajuste de Inventario', href: '/inventory/adjustments' },
        ],
    },
});

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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <ClipboardList class="h-8 w-8 text-primary" />
                    Ajustes de Inventario
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestión de ajustes por merma, deterioro y vencido.
                </p>
            </div>
            <Link href="/inventory/adjustments/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Ajuste
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">N° Ajuste</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Notas</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Items</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="adjustments.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay ajustes registrados</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="adj in adjustments.data" :key="adj.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                #{{ adj.inv_number }}
                            </td>
                            <td class="px-6 py-4">
                                {{ formatDate(adj.inv_date) }}
                            </td>
                            <td class="px-6 py-4 max-w-[200px] truncate">
                                {{ adj.inv_notes || '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                    {{ adj.movements?.length || 0 }} productos
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': adj.inv_status === 'completed',
                                }">
                                    {{ adj.inv_status === 'completed' ? 'Completado' : adj.inv_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/inventory/adjustments/${adj.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Ver Detalles
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="adjustments.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in adjustments.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
