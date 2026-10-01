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
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    damage: any;
    employees: any[];
    statusOptions: string[];
    coinOptions: string[];
    latestRate: any;
    rates: any[];
}>();

const form = useForm({
    eda_employee_id: props.damage.eda_employee_id || '',
    eda_amount: props.damage.eda_amount || '',
    eda_remaining_balance: props.damage.eda_remaining_balance || '',
    eda_coin: props.damage.eda_coin || 'USD',
    eda_valorcam:
        props.damage.eda_valorcam ||
        (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
    eda_description: props.damage.eda_description || '',
    eda_status: props.damage.eda_status || 'activo',
});

const updateExchangeRate = () => {
    if (!props.latestRate) {
        return;
    }

    if (
        form.eda_coin === 'USD' ||
        form.eda_coin === 'BS' ||
        form.eda_coin === 'VES'
    ) {
        form.eda_valorcam = Number(props.latestRate.rate_usd);
    } else if (form.eda_coin === 'COP') {
        form.eda_valorcam = Number(props.latestRate.rate_cop);
    } else if (form.eda_coin === 'EUR') {
        form.eda_valorcam = Number(props.latestRate.rate_eur);
    }
};

const submit = () => {
    form.put(`/employee-damages/${props.damage.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Daño" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/employee-damages"
                    class="rounded-full p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                >
                    <ArrowLeft class="h-6 w-6" />
                </Link>
                <div>
                    <h1
                        class="flex items-center gap-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        <ShieldAlert class="h-8 w-8 text-primary" />
                        Editar Daño
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Modifica los datos del daño.
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
                        v-model="form.eda_employee_id"
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
                    <InputError :message="form.errors.eda_employee_id" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Monto del Daño</label
                    >
                    <Input
                        type="number"
                        step="0.0001"
                        min="0"
                        v-model="form.eda_amount"
                        class="w-full"
                    />
                    <InputError :message="form.errors.eda_amount" />
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
                        v-model="form.eda_remaining_balance"
                        class="w-full"
                    />
                    <InputError :message="form.errors.eda_remaining_balance" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Moneda</label
                    >
                    <select
                        v-model="form.eda_coin"
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
                    <InputError :message="form.errors.eda_coin" />
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
                        v-model="form.eda_valorcam"
                        class="w-full"
                    />
                    <InputError :message="form.errors.eda_valorcam" />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Estado</label
                    >
                    <select
                        v-model="form.eda_status"
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
                    <InputError :message="form.errors.eda_status" />
                </div>

                <div class="md:col-span-3">
                    <label
                        class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >Descripción</label
                    >
                    <textarea
                        v-model="form.eda_description"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:outline-none"
                    ></textarea>
                    <InputError :message="form.errors.eda_description" />
                </div>
            </div>
        </div>
    </div>
</template>
