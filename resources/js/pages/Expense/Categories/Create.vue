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
            { title: 'Nueva', href: '/expense-categories/create' },
        ],
    },
});

const props = defineProps<{
    typeOptions: string[];
}>();

const form = useForm({
    exc_name: '',
    exc_type: 'variable',
    exc_description: '',
});

const submit = () => {
    form.post('/expense-categories', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nueva Categoría de Gasto" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/expense-categories" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <Tags class="h-8 w-8 text-primary" />
                        Nueva Categoría
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Registra una nueva categoría para clasificar gastos.
                    </p>
                </div>
            </div>
            <button
                @click="submit"
                :disabled="form.processing"
                class="inline-flex items-center px-5 py-2.5 bg-primary text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                <Save v-else class="w-4 h-4 mr-2" />
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="bg-red-900/20 border border-red-500/50 p-4 rounded-xl">
            <p class="text-sm text-red-400 font-bold mb-1">Hay problemas con el envío:</p>
            <ul class="list-disc list-inside text-xs text-red-300">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nombre</label>
                    <Input type="text" v-model="form.exc_name" class="w-full" placeholder="Ej: Servicios Públicos" />
                    <InputError :message="form.errors.exc_name" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tipo</label>
                    <select v-model="form.exc_type" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="type in typeOptions" :key="type" :value="type">{{ type }}</option>
                    </select>
                    <InputError :message="form.errors.exc_type" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Descripción</label>
                    <textarea v-model="form.exc_description" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Opcional"></textarea>
                    <InputError :message="form.errors.exc_description" />
                </div>
            </div>
        </div>
    </div>
</template>
