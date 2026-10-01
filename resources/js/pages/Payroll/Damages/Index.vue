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
    };
    statusOptions: string[];
    coinOptions: string[];
}>();
</script>

<template>
    <Head title="Daños de Empleados" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="flex items-center gap-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    <ShieldAlert class="h-8 w-8 text-primary" />
                    Daños
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Gestiona los daños o pérdidas asignadas a empleados.
                </p>
            </div>
            <Link href="/employee-damages/create">
                <Button class="shadow-md transition-shadow hover:shadow-lg">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Daño
                </Button>
            </Link>
        </div>

        <div
            class="flex flex-1 flex-col overflow-hidden rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900"
        >
            <div class="overflow-x-auto">
                <table
                    class="w-full text-left text-sm text-gray-500 dark:text-gray-400"
                >
                    <thead
                        class="bg-gray-50 text-xs text-gray-700 uppercase dark:bg-gray-800 dark:text-gray-300"
                    >
                        <tr>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Empleado
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Monto Original
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Saldo Pendiente
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Moneda
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Descripción
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 dark:border-gray-700"
                            >
                                Estado
                            </th>
                            <th
                                scope="col"
                                class="border-b px-6 py-4 text-right dark:border-gray-700"
                            >
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="damages.data.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-gray-500"
                            >
                                <div class="flex flex-col items-center gap-3">
                                    <FileText
                                        class="h-12 w-12 text-gray-300 dark:text-gray-600"
                                    />
                                    <span class="text-base font-medium"
                                        >No hay daños registrados</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="damage in damages.data"
                            :key="damage.id"
                            class="border-b bg-white transition-colors hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
                        >
                            <td
                                class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ damage.employee?.emp_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ Number(damage.eda_amount).toLocaleString() }}
                            </td>
                            <td
                                class="px-6 py-4 font-semibold"
                                :class="{
                                    'text-green-600 dark:text-green-400':
                                        Number(damage.eda_remaining_balance) <=
                                        0,
                                }"
                            >
                                {{
                                    Number(
                                        damage.eda_remaining_balance,
                                    ).toLocaleString()
                                }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ damage.eda_coin }}
                            </td>
                            <td class="max-w-xs truncate px-6 py-4">
                                {{ damage.eda_description ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200':
                                            damage.eda_status === 'activo',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200':
                                            damage.eda_status === 'pagado',
                                    }"
                                >
                                    {{ damage.eda_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="`/employee-damages/${damage.id}/edit`"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        Editar
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="damages.links.length > 3"
                class="flex justify-center border-t bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex gap-1">
                    <template v-for="(link, key) in damages.links" :key="key">
                        <div
                            v-if="link.url === null"
                            class="cursor-not-allowed rounded border px-4 py-2 text-sm text-gray-400"
                            v-html="link.label"
                        ></div>
                        <Link
                            v-else
                            :href="link.url"
                            class="rounded border px-4 py-2 text-sm transition-colors hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800"
                            :class="{
                                'bg-primary font-bold text-primary-foreground':
                                    link.active,
                            }"
                            v-html="link.label"
                        ></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
