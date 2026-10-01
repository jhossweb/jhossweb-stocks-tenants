<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, HandCoins, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Préstamos', href: '/employee-loans' },
        ],
    },
});

interface Employee {
    id: number;
    emp_name: string;
}

interface Loan {
    id: number;
    elo_employee_id: number;
    elo_amount: string;
    elo_remaining_balance: string;
    elo_coin: string;
    elo_description: string | null;
    elo_status: string;
    employee?: Employee;
}

const props = defineProps<{
    loans: {
        data: Loan[];
        links: any[];
    };
    statusOptions: string[];
    coinOptions: string[];
}>();
</script>

<template>
    <Head title="Préstamos de Empleados" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1
                    class="flex items-center gap-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    <HandCoins class="h-8 w-8 text-primary" />
                    Préstamos
                </h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Gestiona los préstamos otorgados a empleados.
                </p>
            </div>
            <Link href="/employee-loans/create">
                <Button class="shadow-md transition-shadow hover:shadow-lg">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Préstamo
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
                        <tr v-if="loans.data.length === 0">
                            <td
                                colspan="7"
                                class="px-6 py-12 text-center text-gray-500"
                            >
                                <div class="flex flex-col items-center gap-3">
                                    <FileText
                                        class="h-12 w-12 text-gray-300 dark:text-gray-600"
                                    />
                                    <span class="text-base font-medium"
                                        >No hay préstamos registrados</span
                                    >
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="loan in loans.data"
                            :key="loan.id"
                            class="border-b bg-white transition-colors hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
                        >
                            <td
                                class="px-6 py-4 font-medium whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ loan.employee?.emp_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ Number(loan.elo_amount).toLocaleString() }}
                            </td>
                            <td
                                class="px-6 py-4 font-semibold"
                                :class="{
                                    'text-green-600 dark:text-green-400':
                                        Number(loan.elo_remaining_balance) <= 0,
                                }"
                            >
                                {{
                                    Number(
                                        loan.elo_remaining_balance,
                                    ).toLocaleString()
                                }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ loan.elo_coin }}
                            </td>
                            <td class="max-w-xs truncate px-6 py-4">
                                {{ loan.elo_description ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200':
                                            loan.elo_status === 'activo',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200':
                                            loan.elo_status === 'pagado',
                                    }"
                                >
                                    {{ loan.elo_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="`/employee-loans/${loan.id}/edit`">
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
                v-if="loans.links.length > 3"
                class="flex justify-center border-t bg-gray-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex gap-1">
                    <template v-for="(link, key) in loans.links" :key="key">
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
