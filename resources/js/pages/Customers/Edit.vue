<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2, User } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';

const props = defineProps<{
    customer: any;
}>();

const form = useForm({
    clt_code: props.customer.clt_code || '',
    clt_cedula: props.customer.clt_cedula || '',
    clt_name: props.customer.clt_name || '',
    clt_address: props.customer.clt_address || '',
    clt_phone: props.customer.clt_phone || '',
    clt_email: props.customer.clt_email || '',
    clt_website: props.customer.clt_website || '',
    clt_credit_days: props.customer.clt_credit_days ?? 0,
});

const submit = () => {
    form.put(`/customers/${props.customer.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Cliente" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-3xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/customers"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Editar Cliente</h1>
                        <p class="text-xs text-zinc-500">
                            Actualizar datos del cliente
                        </p>
                    </div>
                </div>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500 disabled:opacity-50"
                >
                    <Loader2
                        v-if="form.processing"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Save v-else class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
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
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-sm"
            >
                <div
                    class="mb-6 flex items-center gap-2 text-xs font-bold tracking-widest text-indigo-400 uppercase"
                >
                    <User class="h-4 w-4" /> Datos del Cliente
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Código</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_code"
                            placeholder="C-001"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_code"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_code }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Cédula</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_cedula"
                            placeholder="12345678"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_cedula"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_cedula }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Nombre *</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_name"
                            placeholder="Nombre del cliente"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_name }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Teléfono *</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_phone"
                            placeholder="+58 412-1234567"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_phone"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_phone }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Email *</label
                        >
                        <Input
                            type="email"
                            v-model="form.clt_email"
                            placeholder="cliente@ejemplo.com"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_email"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_email }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Dirección *</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_address"
                            placeholder="Dirección del cliente"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_address"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_address }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Sitio Web</label
                        >
                        <Input
                            type="text"
                            v-model="form.clt_website"
                            placeholder="https://ejemplo.com"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_website"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_website }}
                        </p>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-medium text-zinc-400"
                            >Días de Crédito</label
                        >
                        <Input
                            type="number"
                            v-model="form.clt_credit_days"
                            min="0"
                            max="365"
                            placeholder="0"
                            class="w-full rounded-lg border-zinc-800 bg-zinc-950 text-zinc-200 transition-colors focus:border-indigo-600 focus:ring-indigo-600 sm:text-sm"
                        />
                        <p
                            v-if="form.errors.clt_credit_days"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.clt_credit_days }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
