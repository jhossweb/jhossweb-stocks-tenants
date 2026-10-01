<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Tags, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Categorías de Gastos', href: '/expense-categories' },
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    category: any;
    typeOptions: string[];
}>();

const form = useForm({
    exc_name: props.category.exc_name || '',
    exc_type: props.category.exc_type || 'variable',
    exc_description: props.category.exc_description || '',
});

const submit = () => {
    form.put(`/expense-categories/${props.category.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Categoría de Gasto" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/expense-categories"
                    class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <ArrowLeft class="h-6 w-6" />
                </Link>
                <div>
                    <h1
                        class="flex items-center gap-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        <Tags class="h-8 w-8 text-primary" />
                        Editar Categoría
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Modifica los datos de la categoría.
                    </p>
                </div>
            </div>
            <button
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-lg bg-primary px-5 py-2.5 font-semibold text-white shadow-lg transition-all disabled:opacity-50"
            >
                <Loader2
                    v-if="form.processing"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                <Save v-else class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
            </button>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-red-500/50 bg-red-900/20 p-4"
        >
            <p class="mb-1 text-sm font-bold text-red-400">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-red-300">
                <li v-for="(error, key) in form.errors" :key="key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div
            class="rounded-xl border border-sidebar-border bg-white p-6 shadow-sm dark:bg-gray-900"
        >
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Nombre</label
                    >
                    <Input type="text" v-model="form.exc_name" class="w-full" />
                    <InputError :message="form.errors.exc_name" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Tipo</label
                    >
                    <select
                        v-model="form.exc_type"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        <option
                            v-for="type in typeOptions"
                            :key="type"
                            :value="type"
                        >
                            {{ type }}
                        </option>
                    </select>
                    <InputError :message="form.errors.exc_type" />
                </div>

                <div class="md:col-span-2">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Descripción</label
                    >
                    <textarea
                        v-model="form.exc_description"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    ></textarea>
                    <InputError :message="form.errors.exc_description" />
                </div>
            </div>
        </div>
    </div>
</template>
