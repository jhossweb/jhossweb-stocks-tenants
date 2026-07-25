<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Tags, FileText, Trash2, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';
import FlashMessage from '@/components/FlashMessage.vue';
import type { ExpenseCategory, PaginatedData } from '@/types/Expense';

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
    if (!deletingCategory.value) {
        return;
    }

    router.delete(`/expense-categories/${deletingCategory.value.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelDelete(),
    });
};
</script>

<template>
    <Head title="Categorías de Gastos" />
    <FlashMessage />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/expenses" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Categorías de Gastos</h1>
                        <p class="text-xs text-zinc-500">Clasifica los gastos según su tipo.</p>
                    </div>
                </div>
                <Link href="/expense-categories/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <Tags class="w-4 h-4 mr-2" /> Nueva Categoría
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Nombre</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Descripción</th>
                                <th class="px-4 py-4 text-center">Gastos</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="categories.data.length === 0">
                                <td colspan="5" class="px-4 py-12 text-center text-zinc-500">
                                    <FileText class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay categorías registradas</p>
                                </td>
                            </tr>
                            <tr v-for="category in categories.data" :key="category.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-medium text-zinc-200 whitespace-nowrap">
                                    {{ category.exc_name }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-purple-900/30 text-purple-400': category.exc_type === 'fijo',
                                            'bg-amber-900/30 text-amber-400': category.exc_type === 'variable',
                                        }">
                                        {{ category.exc_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 max-w-xs truncate text-zinc-400">
                                    {{ category.exc_description ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-zinc-800 text-sm font-semibold text-zinc-300">
                                        {{ category.expenses_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/expense-categories/${category.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">
                                            Editar
                                        </Link>
                                        <button
                                            v-if="(category.expenses_count ?? 0) === 0"
                                            @click="confirmDelete(category)"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs text-red-400 hover:bg-red-900/20 rounded-lg transition-colors"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="categories.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in categories.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
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
                        <button @click="cancelDelete" class="px-4 py-2 text-sm border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">Cancelar</button>
                        <button
                            @click="handleDelete"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg font-semibold transition-colors"
                        >
                            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                            Eliminar
                        </button>
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
