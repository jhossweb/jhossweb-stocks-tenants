<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { FileText, CreditCard, Search, X } from 'lucide-vue-next';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Compras', href: '#' },
            { title: 'Cuentas por Pagar', href: '/accounts-payable' },
        ],
    },
});

interface AccountsPayable {
    id: number;
    acp_purchase_number: string;
    acp_total: string;
    acp_balance: string;
    acp_status: string;
    purchase_order?: {
        invoice_number: string;
        supplier?: {
            supplier_name: string;
        };
    };
    created_at: string;
}

const props = defineProps<{
    accountsPayable: {
        data: AccountsPayable[];
        links: any[];
    };
    filters: {
        search?: string;
        status?: string;
    };
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

let timeoutId: ReturnType<typeof setTimeout>;

watch([search, status], () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get('/accounts-payable', {
            search: search.value,
            status: status.value === 'all' ? null : status.value
        }, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
};
</script>

<template>
    <Head title="Cuentas por Pagar" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <CreditCard class="h-8 w-8 text-primary" />
                    Cuentas por Pagar
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Visualiza y gestiona las deudas pendientes con proveedores.
                </p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white dark:bg-gray-900 border border-sidebar-border p-4 rounded-xl shadow-sm">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input 
                    v-model="search" 
                    placeholder="Buscar por factura o proveedor..." 
                    class="pl-10"
                />
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <Select v-model="status">
                    <SelectTrigger class="w-[180px]">
                        <SelectValue placeholder="Estado" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos los estados</SelectItem>
                        <SelectItem value="pending">Pendientes</SelectItem>
                        <SelectItem value="partial">Parciales</SelectItem>
                        <SelectItem value="paid">Pagados</SelectItem>
                    </SelectContent>
                </Select>

                <Button v-if="search || status !== 'all'" variant="ghost" @click="clearFilters" class="text-gray-500 hover:text-red-600 transition-colors">
                    <X class="h-4 w-4 mr-2" />
                    Limpiar Filtros
                </Button>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Factura</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Proveedor</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Total</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Saldo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="accountsPayable.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay cuentas por pagar pendientes</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="account in accountsPayable.data" :key="account.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                #{{ account.acp_purchase_number }}
                            </td>
                            <td class="px-6 py-4">
                                {{ account.purchase_order?.supplier?.supplier_name ?? 'Desconocido' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">
                                ${{ parseFloat(account.acp_total).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold" :class="parseFloat(account.acp_balance) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                ${{ parseFloat(account.acp_balance).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span 
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': account.acp_status === 'pending',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': account.acp_status === 'partial',
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': account.acp_status === 'paid'
                                    }"
                                >
                                    {{ account.acp_status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                    Ver Pagos
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="accountsPayable.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in accountsPayable.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
