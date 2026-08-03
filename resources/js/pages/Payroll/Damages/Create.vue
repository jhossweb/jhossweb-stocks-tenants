<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, ShieldAlert, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Daños', href: '/employee-damages' },
            { title: 'Nuevo', href: '/employee-damages/create' },
        ],
    },
});

const props = defineProps<{
    employees: any[];
    statusOptions: string[];
    coinOptions: string[];
    latestRate: any;
    rates: any[];
}>();

const form = useForm({
    eda_employee_id: '',
    eda_amount: '',
    eda_remaining_balance: '',
    eda_coin: 'USD',
    eda_valorcam: props.latestRate ? Number(props.latestRate.rate_usd) : 1,
    eda_description: '',
    eda_status: 'activo',
});

const updateExchangeRate = () => {
    if (!props.latestRate) {
return;
}

    if (form.eda_coin === 'USD' || form.eda_coin === 'BS' || form.eda_coin === 'VES') {
        form.eda_valorcam = Number(props.latestRate.rate_usd);
    } else if (form.eda_coin === 'COP') {
        form.eda_valorcam = Number(props.latestRate.rate_cop);
    } else if (form.eda_coin === 'EUR') {
        form.eda_valorcam = Number(props.latestRate.rate_eur);
    }
};

const submit = () => {
    form.post('/employee-damages', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo Daño" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/employee-damages" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <ShieldAlert class="h-8 w-8 text-primary" />
                        Nuevo Daño
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Registra un daño o pérdida asignada a un empleado.
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Empleado</label>
                    <select v-model="form.eda_employee_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Seleccione empleado</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.emp_name }} - {{ emp.emp_document }}</option>
                    </select>
                    <InputError :message="form.errors.eda_employee_id" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monto del Daño</label>
                    <Input type="number" step="0.0001" min="0" v-model="form.eda_amount" class="w-full" placeholder="0.0000" />
                    <InputError :message="form.errors.eda_amount" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Saldo Pendiente</label>
                    <Input type="number" step="0.0001" min="0" v-model="form.eda_remaining_balance" class="w-full" placeholder="0.0000" />
                    <InputError :message="form.errors.eda_remaining_balance" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Moneda</label>
                    <select v-model="form.eda_coin" @change="updateExchangeRate" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                    </select>
                    <InputError :message="form.errors.eda_coin" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tasa de Cambio</label>
                    <Input type="number" step="0.0001" min="0" v-model="form.eda_valorcam" class="w-full" />
                    <InputError :message="form.errors.eda_valorcam" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Estado</label>
                    <select v-model="form.eda_status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <InputError :message="form.errors.eda_status" />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Descripción</label>
                    <textarea v-model="form.eda_description" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Describa el daño o pérdida"></textarea>
                    <InputError :message="form.errors.eda_description" />
                </div>
            </div>
        </div>
    </div>
</template>
