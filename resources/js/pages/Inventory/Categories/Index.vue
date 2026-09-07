<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Pencil,
    PlusCircle,
    Tags,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import categoryRoutes from '@/routes/categories';
import type { Category } from '@/types/Inventory';

const props = defineProps<{
    categories: Category[];
}>();

const isDialogOpen = ref(false);
const editingCategory = ref<Category | null>(null);

const form = useForm({
    cat_name: '',
});

const openCreateModal = () => {
    editingCategory.value = null;
    form.reset();
    isDialogOpen.value = true;
};

const openEditModal = (category: Category) => {
    editingCategory.value = category;
    form.cat_name = category.cat_name;
    isDialogOpen.value = true;
};

const saveCategory = () => {
    if (editingCategory.value) {
        form.put(categoryRoutes.update.url(editingCategory.value.id), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(categoryRoutes.store.url(), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteCategory = (id: number) => {
    if (confirm('¿Estás seguro de eliminar esta categoría?')) {
        form.delete(categoryRoutes.destroy.url(id));
    }
};
</script>

<template>
    <Head title="Categorías" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/products" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Categorías</h1>
                        <p class="text-xs text-zinc-500">Administra las categorías del inventario.</p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20"
                >
                    <PlusCircle class="w-4 h-4 mr-2" /> Añadir Categoría
                </button>
            </div>

            <div v-if="form.hasErrors" class="rounded-xl border border-destructive/50 bg-destructive/10 p-4">
                <p v-for="(error, key) in form.errors" :key="key" class="text-sm font-semibold text-destructive">
                    {{ error }}
                </p>
            </div>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{ editingCategory ? 'Editar Categoría' : 'Añadir Nueva Categoría' }}</DialogTitle>
                        <DialogDescription>
                            Ingresa el nombre de la categoría. Haz clic en guardar cuando termines.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="cat_name" class="text-right">Nombre</Label>
                            <div class="col-span-3">
                                <Input
                                    id="cat_name"
                                    v-model="form.cat_name"
                                    placeholder="Ej: Vidrio Templado"
                                    @keyup.enter="saveCategory"
                                    :class="{ 'border-red-500': form.errors.cat_name }"
                                />
                                <p v-if="form.errors.cat_name" class="text-xs text-red-500 mt-1">{{ form.errors.cat_name }}</p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <button type="button" @click="isDialogOpen = false" class="px-4 py-2 text-sm border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">Cancelar</button>
                        <button type="button" @click="saveCategory" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-colors disabled:opacity-50">
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div v-if="props.categories.length === 0" class="bg-zinc-900 border border-dashed border-zinc-700 rounded-xl p-16 flex flex-col items-center justify-center text-zinc-500">
                <Tags class="h-16 w-16 mb-4 opacity-20" />
                <p class="text-lg font-medium text-zinc-400">No hay categorías registradas</p>
                <p class="text-sm text-zinc-600">Comienza añadiendo una nueva categoría.</p>
            </div>

            <div v-else class="bg-zinc-900 border border-zinc-800 rounded-xl shadow-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-800 text-left">
                            <th class="px-6 py-4 font-semibold text-zinc-400">Nombre</th>
                            <th class="px-6 py-4 font-semibold text-zinc-400 text-center">Productos</th>
                            <th class="px-6 py-4 font-semibold text-zinc-400 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="category in props.categories"
                            :key="category.id"
                            class="border-b border-zinc-800/60 hover:bg-zinc-800/40 transition-colors"
                        >
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-500/10 rounded-lg shrink-0">
                                        <Tags class="h-4 w-4 text-indigo-400" />
                                    </div>
                                    <span class="font-medium text-zinc-100">{{ category.cat_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="text-xs px-2.5 py-1 rounded-md bg-zinc-800 text-zinc-300 font-mono">
                                    {{ category.products_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        @click="openEditModal(category)"
                                        class="p-1.5 text-zinc-500 hover:text-indigo-400 hover:bg-indigo-400/10 rounded-md transition-all"
                                        title="Editar"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button
                                        :disabled="(category.products_count ?? 0) > 0"
                                        :title="(category.products_count ?? 0) > 0 ? 'Tiene productos asociados' : 'Eliminar'"
                                        @click="deleteCategory(category.id)"
                                        class="p-1.5 text-zinc-500 hover:text-red-500 hover:bg-red-500/10 rounded-md transition-all disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-zinc-500 disabled:hover:bg-transparent"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-if="props.categories.length > 0" class="text-xs text-zinc-600 px-1">
                {{ props.categories.length }} categoría{{ props.categories.length !== 1 ? 's' : '' }} registrada{{ props.categories.length !== 1 ? 's' : '' }}
            </p>
        </div>
    </div>
</template>
