<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, CreditCard, Search, X, Eye, Pencil, Trash2, Calendar, Hash } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import accountsReceivableRoutes from '@/routes/accounts-receivable';
import receiptRoutes from '@/routes/receipts';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Ventas', href: '#' },
            { title: 'Cuentas por Cobrar', href: '/accounts-receivable' },
        ],
    },
});

interface AccountsReceivable {
    id: number;
    acr_sls_number: string;
    acr_total: string;
    acr_balance: string;
    acr_status: string;
    sale_order?: {
        sls_number: string;
        customer?: {
            clt_name: string;
        };
    };
    payments?: {
        id: number;
        rec_amount: string;
        created_at: string;
        bank_transaction?: {
            btr_reference: string;
            btr_description: string;
            btr_amount: string;
            btr_type: string;
            created_at: string;
            btr_valorcam: string;
        }
    }[];
    created_at: string;
}

const props = defineProps<{
    accountsReceivable: {
        data: AccountsReceivable[];
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
        router.get(accountsReceivableRoutes.index.url({
            query: {
                search: search.value,
                status: status.value === 'all' ? null : status.value
            }
        }), {}, {
            preserveState: true,
            replace: true
        });
    }, 300);
});

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
};

const isPaymentsModalOpen = ref(false);
const selectedAccount = ref<AccountsReceivable | null>(null);

const viewPayments = (account: AccountsReceivable) => {
    selectedAccount.value = account;
    isPaymentsModalOpen.value = true;
};

const editPayment = (paymentId: number) => {
    router.get(`/receipts/${paymentId}/edit`);
};

const deletePayment = (paymentId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este cobro? Esta acción restaurará el balance de la deuda original.')) {
        router.delete(`/receipts/${paymentId}`, {
            onSuccess: () => {
                isPaymentsModalOpen.value = false;
            }
        });
    }
};
</script>

<template>
    <Head title="Cuentas por Cobrar" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <CreditCard class="h-8 w-8 text-primary" />
                    Cuentas por Cobrar
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Visualiza y gestiona las cuentas por cobrar a clientes.
                </p>
            </div>

            <Link :href="receiptRoutes.create.url()">
                <Button class="shadow-md hover:shadow-lg transition-all">
                    <CreditCard class="mr-2 h-4 w-4" />
                    Registrar Cobro
                </Button>
            </Link>
        </div>

        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-white dark:bg-gray-900 border border-sidebar-border p-4 rounded-xl shadow-sm">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                    v-model="search"
                    placeholder="Buscar por factura o cliente..."
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
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Cliente</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Total</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Saldo</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="accountsReceivable.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay cuentas por cobrar pendientes</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="account in accountsReceivable.data" :key="account.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                #{{ account.acr_sls_number }}
                            </td>
                            <td class="px-6 py-4">
                                {{ account.sale_order?.customer?.clt_name ?? 'Desconocido' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-white">
                                ${{ parseFloat(account.acr_total).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold" :class="parseFloat(account.acr_balance) > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                ${{ parseFloat(account.acr_balance).toFixed(2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': account.acr_status === 'pending',
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': account.acr_status === 'partial',
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': account.acr_status === 'paid'
                                    }"
                                >
                                    {{ account.acr_status.toUpperCase() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Button @click="viewPayments(account)" variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                    <Eye class="h-4 w-4 mr-2" />
                                    Ver Cobros
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div v-if="accountsReceivable.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in accountsReceivable.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>

        <!-- Payments Modal -->
        <Dialog :open="isPaymentsModalOpen" @update:open="isPaymentsModalOpen = $event">
            <DialogContent class="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Historial de Cobros</DialogTitle>
                    <DialogDescription>
                        Visualizando los cobros de la factura #{{ selectedAccount?.acr_sls_number }} del cliente {{ selectedAccount?.sale_order?.customer?.clt_name || 'Desconocido' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4">
                    <div v-if="!selectedAccount?.payments || selectedAccount.payments.length === 0" class="text-center py-8 text-gray-500">
                        <FileText class="mx-auto h-8 w-8 text-gray-300 mb-2" />
                        No hay cobros registrados para esta factura.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="payment in selectedAccount.payments" :key="payment.id" class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-semibold text-lg text-gray-900 dark:text-white">
                                        ${{ parseFloat(payment.rec_amount).toFixed(2) }}
                                    </h4>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                        <Calendar class="h-4 w-4" />
                                        {{ new Date(payment.bank_transaction?.created_at || payment.created_at).toLocaleDateString() }}
                                    </div>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1" v-if="payment.bank_transaction?.btr_reference">
                                        <Hash class="h-4 w-4" />
                                        Ref: {{ payment.bank_transaction?.btr_reference }}
                                    </div>
                                    <div class="text-sm mt-2 text-gray-600 dark:text-gray-400" v-if="payment.bank_transaction?.btr_description">
                                        Nota: {{ payment.bank_transaction.btr_description }}
                                    </div>
                                </div>

                                <div class="flex space-x-2">
                                    <Button @click="editPayment(payment.id)" variant="outline" size="sm" class="h-8 shadow-sm">
                                        <Pencil class="h-3.5 w-3.5 mr-1" /> Editar
                                    </Button>
                                    <Button @click="deletePayment(payment.id)" variant="ghost" size="sm" class="h-8 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                    <Button variant="outline" @click="isPaymentsModalOpen = false">Cerrar</Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
