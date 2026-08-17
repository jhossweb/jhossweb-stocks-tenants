<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Users } from 'lucide-vue-next';

interface Customer {
    id: number;
    clt_code: string | null;
    clt_cedula: string | null;
    clt_name: string;
    clt_address: string;
    clt_phone: string;
    clt_email: string;
    clt_website: string | null;
    clt_credit_days: number;
    created_at: string;
}

const { customers } = defineProps<{
    customers: {
        data: Customer[];
        links: any[];
    }
}>();
</script>

<template>
    <Head title="Listado de Clientes" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/dashboard" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Clientes</h1>
                        <p class="text-xs text-zinc-500">Gestiona los clientes registrados en el sistema.</p>
                    </div>
                </div>
                <Link href="/customers/create" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <Users class="w-4 h-4 mr-2" /> Nuevo Cliente
                </Link>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Código</th>
                                <th class="px-4 py-4 text-left">Cédula</th>
                                <th class="px-4 py-4 text-left">Nombre</th>
                                <th class="px-4 py-4 text-left">Días Créd.</th>
                                <th class="px-4 py-4 text-left">Teléfono</th>
                                <th class="px-4 py-4 text-left">Email</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="customers.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                                    <Users class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay clientes registrados</p>
                                </td>
                            </tr>
                            <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs text-zinc-400 whitespace-nowrap">
                                    {{ customer.clt_code || '—' }}
                                </td>
                                <td class="px-4 py-4 font-mono text-xs text-zinc-400 whitespace-nowrap">
                                    {{ customer.clt_cedula || '—' }}
                                </td>
                                <td class="px-4 py-4 font-medium text-zinc-200 whitespace-nowrap">
                                    {{ customer.clt_name }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400 whitespace-nowrap">
                                    <span :class="customer.clt_credit_days > 0 ? 'text-amber-400' : 'text-zinc-500'">{{ customer.clt_credit_days }} días</span>
                                </td>
                                <td class="px-4 py-4 text-zinc-400">{{ customer.clt_phone }}</td>
                                <td class="px-4 py-4 text-zinc-400">{{ customer.clt_email }}</td>
                                <td class="px-4 py-4 text-center">
                                    <Link :href="`/customers/${customer.id}/edit`" class="text-indigo-400 hover:text-indigo-300 text-xs">Editar</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="customers.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in customers.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }"><span v-html="link.label"></span></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
