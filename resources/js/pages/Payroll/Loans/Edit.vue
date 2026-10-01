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
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    loan: any;
    employees: any[];
    statusOptions: string[];
    coinOptions: string[];
    latestRate: any;
    rates: any[];
}>();

const form = useForm({
    elo_employee_id: props.loan.elo_employee_id || '',
    elo_amount: props.loan.elo_amount || '',
    elo_remaining_balance: props.loan.elo_remaining_balance || '',
    elo_coin: props.loan.elo_coin || 'USD',
    elo_valorcam:
        props.loan.elo_valorcam ||
        (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
    elo_description: props.loan.elo_description || '',
    elo_status: props.loan.elo_status || 'activo',
});

const updateExchangeRate = () => {
    if (!props.latestRate) {
        return;
    }

    if (
        form.elo_coin === 'USD' ||
        form.elo_coin === 'BS' ||
        form.elo_coin === 'VES'
    ) {
        form.elo_valorcam = Number(props.latestRate.rate_usd);
    } else if (form.elo_coin === 'COP') {
        form.elo_valorcam = Number(props.latestRate.rate_cop);
    } else if (form.elo_coin === 'EUR') {
        form.elo_valorcam = Number(props.latestRate.rate_eur);
    }
};

const submit = () => {
    form.put(`/employee-loans/${props.loan.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Préstamo" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/employee-loans"
                    class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <ArrowLeft class="h-6 w-6" />
                </Link>
                <div>
                    <h1
                        class="flex items-center gap-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        <HandCoins class="h-8 w-8 text-primary" />
                        Editar Préstamo
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Modifica los datos del préstamo.
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
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Empleado</label
                    >
                    <select
                        v-model="form.elo_employee_id"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        <option value="">Seleccione empleado</option>
                        <option
                            v-for="emp in employees"
                            :key="emp.id"
                            :value="emp.id"
                        >
                            {{ emp.emp_name }} - {{ emp.emp_document }}
                        </option>
                    </select>
                    <InputError :message="form.errors.elo_employee_id" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Monto del Préstamo</label
                    >
                    <Input
                        type="number"
                        step="0.0001"
                        min="0"
                        v-model="form.elo_amount"
                        class="w-full"
                    />
                    <InputError :message="form.errors.elo_amount" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Saldo Pendiente</label
                    >
                    <Input
                        type="number"
                        step="0.0001"
                        min="0"
                        v-model="form.elo_remaining_balance"
                        class="w-full"
                    />
                    <InputError :message="form.errors.elo_remaining_balance" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Moneda</label
                    >
                    <select
                        v-model="form.elo_coin"
                        @change="updateExchangeRate"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        <option
                            v-for="coin in coinOptions"
                            :key="coin"
                            :value="coin"
                        >
                            {{ coin }}
                        </option>
                    </select>
                    <InputError :message="form.errors.elo_coin" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Tasa de Cambio</label
                    >
                    <Input
                        type="number"
                        step="0.0001"
                        min="0"
                        v-model="form.elo_valorcam"
                        class="w-full"
                    />
                    <InputError :message="form.errors.elo_valorcam" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Estado</label
                    >
                    <select
                        v-model="form.elo_status"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    >
                        <option
                            v-for="status in statusOptions"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>
                    <InputError :message="form.errors.elo_status" />
                </div>

                <div class="md:col-span-3">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Descripción</label
                    >
                    <textarea
                        v-model="form.elo_description"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    ></textarea>
                    <InputError :message="form.errors.elo_description" />
                </div>
            </div>
        </div>
    </div>
</template>
