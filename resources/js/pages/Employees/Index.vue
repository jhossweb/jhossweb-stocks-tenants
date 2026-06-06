<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, Users, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Empleados', href: '/employees' },
        ],
    },
});

interface Employee {
    id: number;
    emp_name: string;
    emp_document: string;
    emp_position: string;
    emp_base_salary: string;
    emp_salary_coin: string;
    emp_status: string;
    emp_hire_date: string;
}

const props = defineProps<{
    employees: {
        data: Employee[];
        links: any[];
    }
    statusOptions: string[];
    coinOptions: string[];
}>();
</script>

<template>
    <Head title="Listado de Empleados" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Users class="h-8 w-8 text-primary" />
                    Empleados
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestiona y administra los empleados registrados.
                </p>
            </div>
            <Link href="/employees/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Empleado
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Nombre</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Documento</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Cargo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Salario Base</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="employees.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay empleados registrados</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="employee in employees.data" :key="employee.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ employee.emp_name }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ employee.emp_document }}
                            </td>
                            <td class="px-6 py-4">
                                {{ employee.emp_position }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                {{ Number(employee.emp_base_salary).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4">
                                {{ employee.emp_salary_coin }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': employee.emp_status === 'activo',
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': employee.emp_status === 'inactivo',
                                }">
                                    {{ employee.emp_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/employees/${employee.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Editar
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="employees.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in employees.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
