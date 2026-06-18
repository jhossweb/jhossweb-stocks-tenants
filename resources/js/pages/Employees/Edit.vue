<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Loader2 } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import Input from '@/components/ui/input/Input.vue';
import { today } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Empleados', href: '/employees' },
            { title: 'Editar', href: '' },
        ],
    },
});

const props = defineProps<{
    employee: any;
    statusOptions: string[];
    coinOptions: string[];
}>();

const form = useForm({
    emp_name: props.employee.emp_name || '',
    emp_document: props.employee.emp_document || '',
    emp_phone: props.employee.emp_phone || '',
    emp_email: props.employee.emp_email || '',
    emp_address: props.employee.emp_address || '',
    emp_position: props.employee.emp_position || '',
    emp_hire_date: props.employee.emp_hire_date || today(),
    emp_base_salary: props.employee.emp_base_salary || '',
    emp_salary_coin: props.employee.emp_salary_coin || 'USD',
    emp_status: props.employee.emp_status || 'activo',
});

const submit = () => {
    form.put(`/employees/${props.employee.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar Empleado" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/employees" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <User class="h-8 w-8 text-primary" />
                        Editar Empleado
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        Modifica los datos del empleado.
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
                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
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
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Nombre Completo</label>
                    <Input type="text" v-model="form.emp_name" class="w-full" />
                    <InputError :message="form.errors.emp_name" />
                </div>

                <!-- Documento -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Documento</label>
                    <Input type="text" v-model="form.emp_document" class="w-full" />
                    <InputError :message="form.errors.emp_document" />
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Teléfono</label>
                    <Input type="text" v-model="form.emp_phone" class="w-full" />
                    <InputError :message="form.errors.emp_phone" />
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Email</label>
                    <Input type="email" v-model="form.emp_email" class="w-full" />
                    <InputError :message="form.errors.emp_email" />
                </div>

                <!-- Dirección -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Dirección</label>
                    <Input type="text" v-model="form.emp_address" class="w-full" />
                    <InputError :message="form.errors.emp_address" />
                </div>

                <!-- Cargo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Cargo</label>
                    <Input type="text" v-model="form.emp_position" class="w-full" />
                    <InputError :message="form.errors.emp_position" />
                </div>

                <!-- Fecha de Contratación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Fecha de Contratación</label>
                    <Input type="date" v-model="form.emp_hire_date" class="w-full" />
                    <InputError :message="form.errors.emp_hire_date" />
                </div>

                <!-- Salario Base -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Salario Base</label>
                    <Input type="number" step="0.01" min="0" v-model="form.emp_base_salary" class="w-full" />
                    <InputError :message="form.errors.emp_base_salary" />
                </div>

                <!-- Moneda del Salario -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Moneda del Salario</label>
                    <select v-model="form.emp_salary_coin" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="coin in coinOptions" :key="coin" :value="coin">{{ coin }}</option>
                    </select>
                    <InputError :message="form.errors.emp_salary_coin" />
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Estado</label>
                    <select v-model="form.emp_status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring">
                        <option v-for="status in statusOptions" :key="status" :value="status">{{ status }}</option>
                    </select>
                    <InputError :message="form.errors.emp_status" />
                </div>
            </div>
        </div>
    </div>
</template>
