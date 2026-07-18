<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Tags } from 'lucide-vue-next';
import FormActions from '@/components/Form/FormActions.vue';
import FormSection from '@/components/Form/FormSection.vue';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';

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

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Nueva Categoría"
            subtitle="Registra una nueva categoría para clasificar gastos."
            back-url="/expense-categories"
            :processing="form.processing"
            @save="submit"
        />

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li
                    v-for="(error, key) in form.errors"
                    :key="key"
                >
                    {{ error }}
                </li>
            </ul>
        </div>

        <FormSection>
            <template #header>
                <Tags class="size-4 text-primary" />
                <span>Información de la Categoría</span>
            </template>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Nombre</label>
                    <Input type="text" v-model="form.exc_name" class="w-full" placeholder="Ej: Servicios Públicos" />
                    <InputError :message="form.errors.exc_name" />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Tipo</label>
                    <select v-model="form.exc_type" class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        <option v-for="type in typeOptions" :key="type" :value="type">{{ type }}</option>
                    </select>
                    <InputError :message="form.errors.exc_type" />
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Descripción</label>
                    <textarea v-model="form.exc_description" rows="3" class="bg-background border-input flex w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" placeholder="Opcional"></textarea>
                    <InputError :message="form.errors.exc_description" />
                </div>
            </div>
        </FormSection>
    </div>
</template>
