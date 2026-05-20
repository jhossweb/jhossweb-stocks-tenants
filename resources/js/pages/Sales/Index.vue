<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, FileText, ShoppingBag } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Ventas', href: '/sales' },
        ],
    },
});

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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <ShoppingBag class="h-8 w-8 text-primary" />
                    Ventas
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestiona y registra las ventas realizadas.
                </p>
            </div>
            <Link href="/sales/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nueva Venta
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Factura</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Cliente</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Total</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="sales.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay ventas registradas</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="sale in sales.data" :key="sale.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                #{{ sale.sls_number }}
                            </td>
                            <td class="px-6 py-4">
                                {{ sale.customer?.clt_name ?? 'Desconocido' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ sale.sls_date }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                ${{ sale.sls_total }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': sale.sls_status === 'pending',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': sale.sls_status === 'paid',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': sale.sls_status === 'partial',
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': sale.sls_status === 'cancelled',
                                }">
                                    {{ sale.sls_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/sales/${sale.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Ver Detalles
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="sales.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in sales.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
