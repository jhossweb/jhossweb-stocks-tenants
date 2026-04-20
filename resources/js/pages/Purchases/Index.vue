<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { PlusCircle, FileText, ShoppingCart } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Compras', href: '/purchases' },
        ],
    },
});

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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <ShoppingCart class="h-8 w-8 text-primary" />
                        Órdenes de Compra
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Gestiona y registra el ingreso de mercadería al inventario.
                    </p>
                </div>
                <Link href="/purchases/create">
                    <Button class="shadow-md hover:shadow-lg transition-shadow">
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Nueva Compra
                    </Button>
                </Link>
            </div>

            <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Factura</th>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Proveedor</th>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Total</th>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                                <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="purchases.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                        <span class="text-base font-medium">No hay compras registradas</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="purchase in purchases.data" :key="purchase.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    #{{ purchase.invoice_number }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ purchase.supplier?.supplier_name ?? 'Desconocido' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ purchase.invoice_date }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    ${{ purchase.invoice_total }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ purchase.invoice_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="`/purchases/${purchase.id}/edit`">
                                        <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                            Ver Detalles
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Simple -->
                <div v-if="purchases.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in purchases.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
</template>
