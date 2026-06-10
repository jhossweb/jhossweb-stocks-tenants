<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, ShieldAlert, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Daños', href: '/employee-damages' },
        ],
    },
});

interface Employee {
    id: number;
    emp_name: string;
}

interface Damage {
    id: number;
    eda_employee_id: number;
    eda_amount: string;
    eda_remaining_balance: string;
    eda_coin: string;
    eda_description: string | null;
    eda_status: string;
    employee?: Employee;
}

const props = defineProps<{
    damages: {
        data: Damage[];
        links: any[];
    }
    statusOptions: string[];
    coinOptions: string[];
}>();
</script>

<template>
    <Head title="Daños de Empleados" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <ShieldAlert class="h-8 w-8 text-primary" />
                    Daños
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestiona los daños o pérdidas asignadas a empleados.
                </p>
            </div>
            <Link href="/employee-damages/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Daño
                </Button>
            </Link>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Empleado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Monto Original</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Saldo Pendiente</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Descripción</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="damages.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay daños registrados</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="damage in damages.data" :key="damage.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ damage.employee?.emp_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ Number(damage.eda_amount).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 font-semibold" :class="{ 'text-green-600 dark:text-green-400': Number(damage.eda_remaining_balance) <= 0 }">
                                {{ Number(damage.eda_remaining_balance).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ damage.eda_coin }}
                            </td>
                            <td class="px-6 py-4 max-w-xs truncate">
                                {{ damage.eda_description ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': damage.eda_status === 'activo',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': damage.eda_status === 'pagado',
                                }">
                                    {{ damage.eda_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/employee-damages/${damage.id}/edit`">
                                    <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                        Editar
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="damages.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in damages.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
