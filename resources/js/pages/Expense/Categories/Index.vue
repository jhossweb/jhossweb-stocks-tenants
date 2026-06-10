<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, Tags, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Categorías de Gastos', href: '/expense-categories' },
        ],
    },
});

interface Category {
    id: number;
    exc_name: string;
    exc_type: string;
    exc_description: string | null;
    expenses_count: number;
}

const props = defineProps<{
    categories: {
        data: Category[];
        links: any[];
    }
    typeOptions: string[];
}>();
</script>

<template>
    <Head title="Categorías de Gastos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Tags class="h-8 w-8 text-primary" />
                    Categorías de Gastos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Clasifica los gastos según su tipo.
                </p>
            </div>
            <Link href="/expense-categories/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nueva Categoría
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Nombre</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Tipo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Descripción</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-center">Gastos</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="categories.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay categorías registradas</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="category in categories.data" :key="category.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ category.exc_name }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': category.exc_type === 'fijo',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200': category.exc_type === 'variable',
                                }">
                                    {{ category.exc_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate">
                                {{ category.exc_description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 text-sm font-semibold">
                                    {{ category.expenses_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/expense-categories/${category.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Editar
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="categories.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in categories.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
