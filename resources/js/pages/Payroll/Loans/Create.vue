<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, HandCoins, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Préstamos', href: '/employee-loans' },
            { title: 'Nuevo', href: '/employee-loans/create' },
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
    elo_employee_id: '',
    elo_amount: '',
    elo_remaining_balance: '',
    elo_coin: 'USD',
    elo_valorcam: props.latestRate ? Number(props.latestRate.rate_usd) : 1,
    elo_description: '',
    elo_status: 'activo',
});

const updateExchangeRate = () => {
    if (!props.latestRate) {
return;
}

    if (form.elo_coin === 'USD' || form.elo_coin === 'BS' || form.elo_coin === 'VES') {
        form.elo_valorcam = Number(props.latestRate.rate_usd);
    } else if (form.elo_coin === 'COP') {
        form.elo_valorcam = Number(props.latestRate.rate_cop);
    } else if (form.elo_coin === 'EUR') {
        form.elo_valorcam = Number(props.latestRate.rate_eur);
    }
};

const submit = () => {
    form.post('/employee-loans', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo Préstamo" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/employee-loans" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <HandCoins class="h-8 w-8 text-primary" />
                        Nuevo Préstamo
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Registra un nuevo préstamo para un empleado.
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
                    <select v-model="form.elo_employee_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option value="">Seleccione empleado</option>
                        <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.emp_name }} - {{ emp.emp_document }}</option>
                    </select>
                    <InputError :message="form.errors.elo_employee_id" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Monto del Préstamo</label>
                    <Input type="number" step="0.01" min="0" v-model="form.elo_amount" class="w-full" placeholder="0.00" />
                    <InputError :message="form.errors.elo_amount" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Saldo Pendiente</label>
                    <Input type="number" step="0.01" min="0" v-model="form.elo_remaining_balance" class="w-full" placeholder="0.00" />
                    <InputError :message="form.errors.elo_remaining_balance" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Moneda</label>
                    <select v-model="form.elo_coin" @change="updateExchangeRate" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                    </select>
                    <InputError :message="form.errors.elo_coin" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Tasa de Cambio</label>
                    <Input type="number" step="0.0001" min="0" v-model="form.elo_valorcam" class="w-full" />
                    <InputError :message="form.errors.elo_valorcam" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Estado</label>
                    <select v-model="form.elo_status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <InputError :message="form.errors.elo_status" />
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Descripción</label>
                    <textarea v-model="form.elo_description" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring" placeholder="Motivo del préstamo"></textarea>
                    <InputError :message="form.errors.elo_description" />
                </div>
            </div>
        </div>
    </div>
</template>
