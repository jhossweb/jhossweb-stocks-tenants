<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { PlusCircle, CalendarRange, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/composables/useDate';

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
    }
    statusOptions: string[];
}>();
</script>

<template>
    <Head title="Períodos de Nómina" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between bg-zinc-900/50 backdrop-blur-xl p-6 rounded-2xl border border-zinc-800 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CalendarRange class="w-32 h-32" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <div class="p-2.5 bg-zinc-800/50 rounded-xl border border-zinc-700/50">
                        <CalendarRange class="w-6 h-6 text-indigo-400" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight">
                            Períodos de Nómina
                        </h1>
                        <p class="text-xs text-zinc-500 font-medium tracking-wide">
                            Gestiona los períodos de pago de nómina
                        </p>
                    </div>
                </div>

                <Link href="/payroll-periods/create" class="relative z-10">
                    <Button class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-12 px-6 rounded-xl shadow-lg shadow-indigo-500/20 active:scale-95 transition-all">
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Nuevo Período
                    </Button>
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-zinc-400">
                        <thead class="text-[10px] text-zinc-500 uppercase tracking-wider font-black bg-zinc-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800">Fecha Inicio</th>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800">Fecha Fin</th>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800">Fecha Pago</th>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800">Estado</th>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800 text-center">Nóminas</th>
                                <th scope="col" class="px-6 py-4 border-b border-zinc-800 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="periods.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center text-zinc-500">
                                    <div class="flex flex-col items-center gap-3">
                                        <FileText class="w-12 h-12 text-zinc-700" />
                                        <span class="text-base font-medium">No hay períodos registrados</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="period in periods.data" :key="period.id" class="border-b border-zinc-800/50 hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-4 font-medium text-zinc-100 whitespace-nowrap">
                                    {{ formatDate(period.pp_start_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(period.pp_end_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(period.pp_payment_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="{
                                        'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20': period.pp_status === 'pendiente',
                                        'bg-green-500/10 text-green-400 border border-green-500/20': period.pp_status === 'pagado',
                                    }">
                                        {{ period.pp_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-zinc-800 text-sm font-bold text-zinc-300">
                                        {{ period.payrolls_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="`/payroll-periods/${period.id}/edit`">
                                        <Button variant="ghost" size="sm" class="text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg">
                                            Editar
                                        </Button>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="periods.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center bg-zinc-900">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in periods.links" :key="key">
                            <div v-if="link.url === null" class="px-3 py-1.5 text-xs text-zinc-600 border border-zinc-800 rounded-lg cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1.5 text-xs border border-zinc-800 rounded-lg hover:bg-zinc-800 transition-colors" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
