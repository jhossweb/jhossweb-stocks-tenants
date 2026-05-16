<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { PlusCircle, Users, FileText } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Clientes', href: '/customers' },
        ],
    },
});

interface Customer {
    id: number;
    clt_name: string;
    clt_address: string;
    clt_phone: string;
    clt_email: string;
    clt_website: string | null;
    created_at: string;
}

const props = defineProps<{
    customers: {
        data: Customer[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Listado de Clientes" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Users class="h-8 w-8 text-primary" />
                    Clientes
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestiona los clientes registrados en el sistema.
                </p>
            </div>
            <Link href="/customers/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Cliente
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Nombre</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Teléfono</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Email</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Dirección</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay clientes registrados</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="customer in customers.data" :key="customer.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ customer.clt_name }}
                            </td>
                            <td class="px-6 py-4">
                                {{ customer.clt_phone }}
                            </td>
                            <td class="px-6 py-4">
                                {{ customer.clt_email }}
                            </td>
                            <td class="px-6 py-4 max-w-[200px] truncate">
                                {{ customer.clt_address }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/customers/${customer.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Editar
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="customers.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in customers.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
