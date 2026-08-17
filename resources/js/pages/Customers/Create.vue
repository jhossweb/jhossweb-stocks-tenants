<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Loader2, User } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';

const form = useForm({
    clt_code: '',
    clt_cedula: '',
    clt_name: '',
    clt_address: '',
    clt_phone: '',
    clt_email: '',
    clt_website: '',
    clt_credit_days: 0,
});

const submit = () => {
    form.post('/customers', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Nuevo Cliente" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/customers" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Nuevo Cliente</h1>
                        <p class="text-xs text-zinc-500">Registro de cliente en el sistema</p>
                    </div>
                </div>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg shadow-indigo-900/20"
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

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-6 shadow-sm">
                <div class="flex items-center gap-2 text-indigo-400 font-bold text-xs uppercase tracking-widest mb-6">
                    <User class="w-4 h-4" /> Datos del Cliente
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Código</label>
                        <Input type="text" v-model="form.clt_code" placeholder="C-001" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_code" class="text-xs text-red-500 mt-1">{{ form.errors.clt_code }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Cédula</label>
                        <Input type="text" v-model="form.clt_cedula" placeholder="12345678" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_cedula" class="text-xs text-red-500 mt-1">{{ form.errors.clt_cedula }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Nombre *</label>
                        <Input type="text" v-model="form.clt_name" placeholder="Nombre del cliente" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_name" class="text-xs text-red-500 mt-1">{{ form.errors.clt_name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Teléfono *</label>
                        <Input type="text" v-model="form.clt_phone" placeholder="+58 412-1234567" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_phone" class="text-xs text-red-500 mt-1">{{ form.errors.clt_phone }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Email *</label>
                        <Input type="email" v-model="form.clt_email" placeholder="cliente@ejemplo.com" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_email" class="text-xs text-red-500 mt-1">{{ form.errors.clt_email }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Dirección *</label>
                        <Input type="text" v-model="form.clt_address" placeholder="Dirección del cliente" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_address" class="text-xs text-red-500 mt-1">{{ form.errors.clt_address }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Sitio Web</label>
                        <Input type="text" v-model="form.clt_website" placeholder="https://ejemplo.com" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_website" class="text-xs text-red-500 mt-1">{{ form.errors.clt_website }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-1.5">Días de Crédito</label>
                        <Input type="number" v-model="form.clt_credit_days" min="0" max="365" placeholder="0" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                        <p v-if="form.errors.clt_credit_days" class="text-xs text-red-500 mt-1">{{ form.errors.clt_credit_days }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
