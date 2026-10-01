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

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/expenses"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Categorías de Gastos</h1>
                        <p class="text-xs text-zinc-500">
                            Clasifica los gastos según su tipo.
                        </p>
                    </div>
                </div>
                <Link
                    href="/expense-categories/create"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <Tags class="mr-2 h-4 w-4" /> Nueva Categoría
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Nombre</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Descripción</th>
                                <th class="px-4 py-4 text-center">Gastos</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="categories.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <FileText
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay categorías registradas</p>
                                </td>
                            </tr>
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td
                                    class="px-4 py-4 font-medium whitespace-nowrap text-zinc-200"
                                >
                                    {{ category.exc_name }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs"
                                        :class="{
                                            'bg-purple-900/30 text-purple-400':
                                                category.exc_type === 'fijo',
                                            'bg-amber-900/30 text-amber-400':
                                                category.exc_type ===
                                                'variable',
                                        }"
                                    >
                                        {{ category.exc_type }}
                                    </span>
                                </td>
                                <td
                                    class="max-w-xs truncate px-4 py-4 text-zinc-400"
                                >
                                    {{ category.exc_description ?? '-' }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-800 text-sm font-semibold text-zinc-300"
                                    >
                                        {{ category.expenses_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="`/expense-categories/${category.id}/edit`"
                                            class="text-xs text-indigo-400 hover:text-indigo-300"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            v-if="
                                                (category.expenses_count ??
                                                    0) === 0
                                            "
                                            @click="confirmDelete(category)"
                                            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-red-400 transition-colors hover:bg-red-900/20"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="categories.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in categories.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                v-html="link.label"
                            ></Link>
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
                class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/80 p-4 backdrop-blur-sm"
                @click.self="cancelDelete"
            >
                <div
                    class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900 p-6 shadow-2xl"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded-lg bg-red-500/10 p-2">
                            <Trash2 class="h-5 w-5 text-red-500" />
                        </div>
                        <h3 class="text-lg font-bold text-zinc-100">
                            Eliminar Categoría
                        </h3>
                    </div>
                    <p class="mb-6 text-sm text-zinc-400">
                        ¿Estás seguro de que deseas eliminar la categoría
                        "<strong class="text-zinc-200">{{
                            deletingCategory?.exc_name
                        }}</strong
                        >"? Esta acción no se puede deshacer.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button
                            @click="cancelDelete"
                            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="handleDelete"
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-red-500"
                        >
                            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
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
