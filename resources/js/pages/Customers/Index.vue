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
    };
}>();
</script>

<template>
    <Head title="Listado de Clientes" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/dashboard"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Clientes</h1>
                        <p class="text-xs text-zinc-500">
                            Gestiona los clientes registrados en el sistema.
                        </p>
                    </div>
                </div>
                <Link
                    href="/customers/create"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                >
                    <Users class="mr-2 h-4 w-4" /> Nuevo Cliente
                </Link>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Código</th>
                                <th class="px-4 py-4 text-left">Cédula</th>
                                <th class="px-4 py-4 text-left">Nombre</th>
                                <th class="px-4 py-4 text-left">Días Créd.</th>
                                <th class="px-4 py-4 text-left">Teléfono</th>
                                <th class="px-4 py-4 text-left">Email</th>
                                <th class="w-12 px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="customers.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <Users
                                        class="mx-auto mb-4 h-12 w-12 text-zinc-600"
                                    />
                                    <p>No hay clientes registrados</p>
                                </td>
                            </tr>
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-400"
                                >
                                    {{ customer.clt_code || '—' }}
                                </td>
                                <td
                                    class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-400"
                                >
                                    {{ customer.clt_cedula || '—' }}
                                </td>
                                <td
                                    class="px-4 py-4 font-medium whitespace-nowrap text-zinc-200"
                                >
                                    {{ customer.clt_name }}
                                </td>
                                <td
                                    class="px-4 py-4 whitespace-nowrap text-zinc-400"
                                >
                                    <span
                                        :class="
                                            customer.clt_credit_days > 0
                                                ? 'text-amber-400'
                                                : 'text-zinc-500'
                                        "
                                        >{{
                                            customer.clt_credit_days
                                        }}
                                        días</span
                                    >
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{ customer.clt_phone }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{ customer.clt_email }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <Link
                                        :href="`/customers/${customer.id}/edit`"
                                        class="text-xs text-indigo-400 hover:text-indigo-300"
                                        >Editar</Link
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="customers.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template
                            v-for="(link, key) in customers.links"
                            :key="key"
                        >
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-500"
                                v-html="link.label"
                            ></div>
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                ><span v-html="link.label"></span
                            ></Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
