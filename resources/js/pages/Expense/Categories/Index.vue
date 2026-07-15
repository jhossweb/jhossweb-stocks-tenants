<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusCircle, Tags, FileText, Trash2, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import FlashMessage from '@/components/FlashMessage.vue';
import { Button } from '@/components/ui/button';
import type { ExpenseCategory, PaginatedData } from '@/types/Expense';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Categorías de Gastos', href: '/expense-categories' },
        ],
    },
});

const props = defineProps<{
    categories: PaginatedData<ExpenseCategory>;
    typeOptions: string[];
}>();

const showDeleteConfirm = ref(false);
const deletingCategory = ref<ExpenseCategory | null>(null);

const confirmDelete = (category: ExpenseCategory) => {
    deletingCategory.value = category;
    showDeleteConfirm.value = true;
};

const cancelDelete = () => {
    showDeleteConfirm.value = false;
    deletingCategory.value = null;
};

const handleDelete = () => {
    if (!deletingCategory.value) return;
    router.delete(`/expense-categories/${deletingCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelDelete(),
    });
};
</script>

<template>
    <Head title="Categorías de Gastos" />
    <FlashMessage />

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
                                    {{ category.expenses_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/expense-categories/${category.id}/edit`">
                                        <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                            Editar
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="(category.expenses_count ?? 0) === 0"
                                        variant="ghost"
                                        size="sm"
                                        @click="confirmDelete(category)"
                                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
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

    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="showDeleteConfirm"
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 backdrop-blur-sm p-4"
                @click.self="cancelDelete"
            >
                <div class="bg-zinc-900 rounded-2xl border border-zinc-800 shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-red-500/10 rounded-lg">
                            <Trash2 class="w-5 h-5 text-red-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-100">Eliminar Categoría</h3>
                    </div>
                    <p class="text-sm text-zinc-400 mb-6">
                        ¿Estás seguro de que deseas eliminar la categoría "<strong class="text-zinc-200">{{ deletingCategory?.exc_name }}</strong>"? Esta acción no se puede deshacer.
                    </p>
                    <div class="flex justify-end gap-3">
                        <Button variant="outline" @click="cancelDelete">Cancelar</Button>
                        <Button
                            @click="handleDelete"
                            class="bg-red-600 hover:bg-red-500 text-white font-bold"
                        >
                            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                            Eliminar
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
