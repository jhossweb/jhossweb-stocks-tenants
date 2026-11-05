<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, CalendarRange, FileText } from 'lucide-vue-next';
import DocumentLifecycleMenu from '@/components/DocumentLifecycleMenu.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/Auth/usePermissions';
import { formatDate } from '@/composables/useDate';

const { can } = usePermissions();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Períodos de Nómina', href: '/payroll-periods' },
        ],
    },
});

interface Period {
    id: number;
    is_active: boolean;
    voided_at?: string | null;
    pp_start_date: string;
    pp_end_date: string;
    pp_payment_date: string | null;
    pp_status: string;
    payrolls_count: number;
}

defineProps<{
    periods: {
        data: Period[];
        links: any[];
    };
    statusOptions: string[];
}>();
</script>

<template>
    <Head title="Períodos de Nómina" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <!-- Header -->
            <div
                class="relative flex items-center justify-between overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/50 p-6 shadow-2xl backdrop-blur-xl"
            >
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CalendarRange class="h-32 w-32" />
                </div>

                <div class="relative z-10 flex items-center gap-4">
                    <div
                        class="rounded-xl border border-zinc-700/50 bg-zinc-800/50 p-2.5"
                    >
                        <CalendarRange class="h-6 w-6 text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight">
                            Períodos de Nómina
                        </h1>
                        <p
                            class="text-xs font-medium tracking-wide text-zinc-500"
                        >
                            Gestiona los períodos de pago de nómina
                        </p>
                    </div>
                </div>

                <Link href="/payroll-periods/create" class="relative z-10">
                    <Button
                        class="h-12 rounded-xl bg-indigo-600 px-6 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:bg-indigo-500 active:scale-95"
                    >
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Nuevo Período
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-400">
                        <thead
                            class="bg-zinc-800/50 text-[10px] font-black tracking-wider text-zinc-500 uppercase"
                        >
                            <tr>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4"
                                >
                                    Fecha Inicio
                                </th>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4"
                                >
                                    Fecha Fin
                                </th>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4"
                                >
                                    Fecha Pago
                                </th>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4"
                                >
                                    Estado
                                </th>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4 text-center"
                                >
                                    Nóminas
                                </th>
                                <th
                                    scope="col"
                                    class="border-b border-zinc-800 px-6 py-4 text-right"
                                >
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="periods.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-16 text-center text-zinc-500"
                                >
                                    <div
                                        class="flex flex-col items-center gap-3"
                                    >
                                        <FileText
                                            class="h-12 w-12 text-zinc-700"
                                        />
                                        <span class="text-base font-medium"
                                            >No hay períodos registrados</span
                                        >
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="period in periods.data"
                                :key="period.id"
                                class="border-b border-zinc-800/50 transition-colors hover:bg-zinc-800/30"
                            >
                                <td
                                    class="px-6 py-4 font-medium whitespace-nowrap text-zinc-100"
                                >
                                    {{ formatDate(period.pp_start_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(period.pp_end_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(period.pp_payment_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                                        :class="{
                                            'border border-yellow-500/20 bg-yellow-500/10 text-yellow-400':
                                                period.pp_status ===
                                                'pendiente',
                                            'border border-green-500/20 bg-green-500/10 text-green-400':
                                                period.pp_status === 'pagado',
                                        }"
                                    >
                                        {{ period.pp_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-800 text-sm font-bold text-zinc-300"
                                    >
                                        {{ period.payrolls_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="`/payroll-periods/${period.id}/edit`"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="rounded-lg text-zinc-400 hover:bg-zinc-800 hover:text-white"
                                            >
                                                Editar
                                            </Button>
                                        </Link>
                                        <DocumentLifecycleMenu
                                            type="payroll-periods"
                                            :record="period"
                                            :can-activate="
                                                can('payroll.periods.activate')
                                            "
                                            :can-deactivate="
                                                can(
                                                    'payroll.periods.deactivate',
                                                )
                                            "
                                            :can-void="
                                                can('payroll.periods.void')
                                            "
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="periods.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 bg-zinc-900 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in periods.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded-lg border border-zinc-800 px-3 py-1.5 text-xs text-zinc-600"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-lg border border-zinc-800 px-3 py-1.5 text-xs transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                v-html="link.label"
                            ></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
