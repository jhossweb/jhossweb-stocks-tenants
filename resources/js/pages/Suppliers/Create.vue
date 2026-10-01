<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2, Building2 } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';

const form = useForm({
    supplier_name: '',
    supplier_address: '',
    supplier_phone: '',
    supplier_email: '',
    supplier_website: '',
    supplier_credit_days: 0,
});

const submit = () => {
    form.post('/suppliers', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Nuevo Proveedor" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div
            class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <Link
                    href="/suppliers"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">Nuevo Proveedor</h1>
                    <p class="text-xs text-muted-foreground">
                        Registro de proveedor en el sistema
                    </p>
                </div>
            </div>
            <button
                type="button"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50"
                @click="submit"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in form.errors" :key="key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="rounded-xl border bg-card shadow-sm">
            <div class="flex items-center gap-2 border-b px-6 py-4">
                <Building2 class="size-4 text-primary" />
                <span
                    class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                    >Datos del Proveedor</span
                >
            </div>
            <div class="flex flex-col gap-5 p-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Nombre *</label
                        >
                        <Input
                            type="text"
                            v-model="form.supplier_name"
                            placeholder="Nombre del proveedor"
                            :class="{
                                'border-destructive': form.errors.supplier_name,
                            }"
                        />
                        <p
                            v-if="form.errors.supplier_name"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Teléfono *</label
                        >
                        <Input
                            type="text"
                            v-model="form.supplier_phone"
                            placeholder="+58 412-1234567"
                            :class="{
                                'border-destructive':
                                    form.errors.supplier_phone,
                            }"
                        />
                        <p
                            v-if="form.errors.supplier_phone"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_phone }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Email *</label
                        >
                        <Input
                            type="email"
                            v-model="form.supplier_email"
                            placeholder="proveedor@ejemplo.com"
                            :class="{
                                'border-destructive':
                                    form.errors.supplier_email,
                            }"
                        />
                        <p
                            v-if="form.errors.supplier_email"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_email }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Dirección *</label
                        >
                        <Input
                            type="text"
                            v-model="form.supplier_address"
                            placeholder="Dirección del proveedor"
                            :class="{
                                'border-destructive':
                                    form.errors.supplier_address,
                            }"
                        />
                        <p
                            v-if="form.errors.supplier_address"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_address }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Sitio Web</label
                        >
                        <Input
                            type="text"
                            v-model="form.supplier_website"
                            placeholder="https://ejemplo.com"
                        />
                        <p
                            v-if="form.errors.supplier_website"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_website }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-muted-foreground"
                            >Días de Crédito</label
                        >
                        <Input
                            type="number"
                            v-model="form.supplier_credit_days"
                            min="0"
                            max="365"
                            placeholder="0"
                        />
                        <p
                            v-if="form.errors.supplier_credit_days"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ form.errors.supplier_credit_days }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
