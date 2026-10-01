<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Boxes, Pencil, PlusCircle, Trash2 } from 'lucide-vue-next';
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
import warehouseRoutes from '@/routes/warehouses';
import type { Warehouse } from '@/types/Inventory';

const props = defineProps<{
    warehouses: Warehouse[];
}>();

const isDialogOpen = ref(false);
const editingWarehouse = ref<Warehouse | null>(null);

const form = useForm({
    war_name: '',
});

const openCreateModal = () => {
    editingWarehouse.value = null;
    form.reset();
    isDialogOpen.value = true;
};

const openEditModal = (warehouse: Warehouse) => {
    editingWarehouse.value = warehouse;
    form.war_name = warehouse.war_name;
    isDialogOpen.value = true;
};

const saveWarehouse = () => {
    if (editingWarehouse.value) {
        form.put(warehouseRoutes.update.url(editingWarehouse.value.id), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(warehouseRoutes.store.url(), {
            onSuccess: () => {
                isDialogOpen.value = false;
                form.reset();
            },
        });
    }
};

const deleteWarehouse = (id: number) => {
    if (confirm('¿Estás seguro de eliminar este almacén?')) {
        form.delete(warehouseRoutes.destroy.url(id));
    }
};
</script>

<template>
    <Head title="Almacenes" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/products"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Almacenes</h1>
                        <p class="text-xs text-zinc-500">
                            Administra los almacenes y bodegas del inventario.
                        </p>
                    </div>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <PlusCircle class="mr-2 h-4 w-4" /> Añadir Almacén
                </button>
            </div>

            <div
                v-if="form.hasErrors"
                class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
            >
                <p
                    v-for="(error, key) in form.errors"
                    :key="key"
                    class="text-sm font-semibold text-destructive"
                >
                    {{ error }}
                </p>
            </div>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>{{
                            editingWarehouse
                                ? 'Editar Almacén'
                                : 'Añadir Nuevo Almacén'
                        }}</DialogTitle>
                        <DialogDescription>
                            Ingresa el nombre del almacén. Haz clic en guardar
                            cuando termines.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid grid-cols-4 items-center gap-4">
                            <Label for="war_name" class="text-right"
                                >Nombre</Label
                            >
                            <div class="col-span-3">
                                <Input
                                    id="war_name"
                                    v-model="form.war_name"
                                    placeholder="Ej: Almacén Principal"
                                    @keyup.enter="saveWarehouse"
                                    :class="{
                                        'border-red-500': form.errors.war_name,
                                    }"
                                />
                                <p
                                    v-if="form.errors.war_name"
                                    class="mt-1 text-xs text-red-500"
                                >
                                    {{ form.errors.war_name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <button
                            type="button"
                            @click="isDialogOpen = false"
                            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                        >
                            Cancelar
                        </button>
                        <button
                            type="button"
                            @click="saveWarehouse"
                            :disabled="form.processing"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white transition-colors hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div
                v-if="props.warehouses.length === 0"
                class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-700 bg-zinc-900 p-16 text-zinc-500"
            >
                <Boxes class="mb-4 h-16 w-16 opacity-20" />
                <p class="text-lg font-medium text-zinc-400">
                    No hay almacenes registrados
                </p>
                <p class="text-sm text-zinc-600">
                    Comienza añadiendo un nuevo almacén.
                </p>
            </div>

            <div
                v-else
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-xl"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-800 text-left">
                            <th class="px-6 py-4 font-semibold text-zinc-400">
                                Nombre
                            </th>
                            <th
                                class="px-6 py-4 text-center font-semibold text-zinc-400"
                            >
                                Registros de Stock
                            </th>
                            <th
                                class="px-6 py-4 text-center font-semibold text-zinc-400"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="warehouse in props.warehouses"
                            :key="warehouse.id"
                            class="border-b border-zinc-800/60 transition-colors hover:bg-zinc-800/40"
                        >
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="shrink-0 rounded-lg bg-indigo-500/10 p-2"
                                    >
                                        <Boxes
                                            class="h-4 w-4 text-indigo-400"
                                        />
                                    </div>
                                    <span class="font-medium text-zinc-100">{{
                                        warehouse.war_name
                                    }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-center">
                                <span
                                    class="rounded-md bg-zinc-800 px-2.5 py-1 font-mono text-xs text-zinc-300"
                                >
                                    {{ warehouse.stocks_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div
                                    class="flex items-center justify-center gap-2"
                                >
                                    <button
                                        @click="openEditModal(warehouse)"
                                        class="rounded-md p-1.5 text-zinc-500 transition-all hover:bg-indigo-400/10 hover:text-indigo-400"
                                        title="Editar"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button
                                        :disabled="
                                            (warehouse.stocks_count ?? 0) > 0
                                        "
                                        :title="
                                            (warehouse.stocks_count ?? 0) > 0
                                                ? 'Tiene registros de stock o movimientos'
                                                : 'Eliminar'
                                        "
                                        @click="deleteWarehouse(warehouse.id)"
                                        class="rounded-md p-1.5 text-zinc-500 transition-all hover:bg-red-500/10 hover:text-red-500 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-zinc-500"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p
                v-if="props.warehouses.length > 0"
                class="px-1 text-xs text-zinc-600"
            >
                {{ props.warehouses.length }} almacén{{
                    props.warehouses.length !== 1 ? 'es' : ''
                }}
                registrado{{ props.warehouses.length !== 1 ? 's' : '' }}
            </p>
        </div>
    </div>
</template>
