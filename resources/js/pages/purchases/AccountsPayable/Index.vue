<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, FileText, Search, X, Eye, Pencil, Trash2, Calendar, Hash, Undo2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import { formatDate } from '@/composables/useDate';
import accountsPayableRoutes from '@/routes/accounts-payable';
import paymentRoutes from '@/routes/payments';

interface AccountsPayable {
    id: number;
    acp_purchase_number: string;
    acp_total: string;
    acp_balance: string;
    acp_status: string;
    acp_total_usd: string | null;
    acp_balance_usd: string | null;
    purchase_order?: {
        invoice_number: string;
        invoice_coin?: string;
        supplier?: {
            supplier_name: string;
        };
    };
    payments?: {
        id: number;
        apy_amount: string;
        apy_type: string;
        apy_invoice_reference: string | null;
        created_at: string;
        bank_transaction?: {
            btr_reference: string;
            btr_description: string;
            btr_amount: string;
            btr_type: string;
            btr_coin: string;
            created_at: string;
            btr_valorcam: string;
            btr_diferencial: string;
        }
    }[];
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
        router.get(accountsPayableRoutes.index.url({
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
const selectedAccount = ref<AccountsPayable | null>(null);

const viewPayments = (account: AccountsPayable) => {
    selectedAccount.value = account;
    isPaymentsModalOpen.value = true;
};

const editPayment = (paymentId: number) => {
    router.get(`/payments/${paymentId}/edit`);
};

const deletePayment = (paymentId: number) => {
    if (confirm('¿Estás seguro de que deseas eliminar este pago? Esta acción restaurará el balance de la deuda original.')) {
        router.delete(`/payments/${paymentId}`, {
            onSuccess: () => {
                isPaymentsModalOpen.value = false;
            }
        });
    }
};
</script>

<template>
    <Head title="Cuentas por Pagar" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/purchases" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Cuentas por Pagar</h1>
                        <p class="text-xs text-zinc-500">Visualiza y gestiona las deudas pendientes con proveedores.</p>
                    </div>
                </div>
                <Link :href="paymentRoutes.create.url()" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <CreditCard class="w-4 h-4 mr-2" /> Registrar Pago
                </Link>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input
                            v-model="search"
                            placeholder="Buscar por factura o proveedor..."
                            class="pl-10 bg-zinc-800 border-zinc-700 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="status">
                            <SelectTrigger class="w-[180px] bg-zinc-800 border-zinc-700 text-zinc-100">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos los estados</SelectItem>
                                <SelectItem value="pending">Pendientes</SelectItem>
                                <SelectItem value="partial">Parciales</SelectItem>
                                <SelectItem value="paid">Pagados</SelectItem>
                            </SelectContent>
                        </Select>

                        <button v-if="search || status !== 'all'" @click="clearFilters" class="inline-flex items-center gap-2 px-3 py-2 text-sm text-zinc-400 hover:text-red-400 transition-colors rounded-lg hover:bg-zinc-800">
                            <X class="h-4 w-4" />
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Factura</th>
                                <th class="px-4 py-4 text-left">Proveedor</th>
                                <th class="px-4 py-4 text-right">Total</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 text-center">Estado</th>
                                <th class="px-4 py-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="accountsPayable.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                    <CreditCard class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay cuentas por pagar pendientes</p>
                                </td>
                            </tr>
                            <tr v-for="account in accountsPayable.data" :key="account.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ account.acp_purchase_number }}</td>
                                <td class="px-4 py-4">{{ account.purchase_order?.supplier?.supplier_name ?? 'Desconocido' }}</td>
                                <td class="px-4 py-4 text-right font-mono">
                                    {{ parseFloat(account.acp_total).toFixed(4) }}
                                    <span v-if="account.purchase_order?.invoice_coin" class="text-[10px] text-zinc-500 ml-1">{{ account.purchase_order.invoice_coin }}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-mono" :class="parseFloat(account.acp_balance) > 0 ? 'text-red-400' : 'text-green-400'">
                                    {{ parseFloat(account.acp_balance).toFixed(4) }}
                                    <span v-if="account.purchase_order?.invoice_coin" class="text-[10px] ml-1 opacity-60">{{ account.purchase_order.invoice_coin }}</span>
                                    <div v-if="account.acp_balance_usd && account.purchase_order?.invoice_coin !== 'USD'" class="text-[10px] text-zinc-500 font-normal">
                                        ${{ parseFloat(account.acp_balance_usd).toFixed(4) }} USD
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-xs px-2 py-1 rounded-full"
                                        :class="{
                                            'bg-red-900/30 text-red-400': account.acp_status === 'pending',
                                            'bg-blue-900/30 text-blue-400': account.acp_status === 'partial',
                                            'bg-green-900/30 text-green-400': account.acp_status === 'paid'
                                        }">
                                        {{ account.acp_status.toUpperCase() }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button @click="viewPayments(account)" class="text-indigo-400 hover:text-indigo-300 text-xs inline-flex items-center gap-1">
                                        <Eye class="h-3.5 w-3.5" /> Pagos
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="accountsPayable.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in accountsPayable.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Payments Modal -->
            <Dialog :open="isPaymentsModalOpen" @update:open="isPaymentsModalOpen = $event">
                <DialogContent class="sm:max-w-2xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Historial de Pagos</DialogTitle>
                        <DialogDescription>
                            Visualizando los pagos de la factura #{{ selectedAccount?.acp_purchase_number }} del proveedor {{ selectedAccount?.purchase_order?.supplier?.supplier_name || 'Desconocido' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4">
                        <div v-if="!selectedAccount?.payments || selectedAccount.payments.length === 0" class="text-center py-8 text-gray-500">
                            <FileText class="mx-auto h-8 w-8 text-gray-300 mb-2" />
                            No hay pagos registrados para esta factura.
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="payment in selectedAccount.payments" :key="payment.id" class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700" :class="{ 'border-orange-300 dark:border-orange-700': payment.apy_type === 'RETURN_CREDIT' }">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold text-lg text-gray-900 dark:text-white">
                                                ${{ parseFloat(payment.apy_amount).toFixed(4) }}
                                                <span v-if="payment.bank_transaction?.btr_coin" class="text-xs font-normal text-gray-400 ml-1">{{ payment.bank_transaction.btr_coin }}</span>
                                            </h4>
                                            <span v-if="payment.apy_type === 'RETURN_CREDIT'" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                Crédito por devolución
                                            </span>
                                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                Pago
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                            <Calendar class="h-4 w-4" />
                                            {{ formatDate(payment.bank_transaction?.created_at ?? payment.created_at) }}
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1" v-if="payment.apy_invoice_reference">
                                            <Undo2 class="h-4 w-4" />
                                            Dev: #{{ payment.apy_invoice_reference }}
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1" v-if="payment.bank_transaction?.btr_reference">
                                            <Hash class="h-4 w-4" />
                                            Ref: {{ payment.bank_transaction?.btr_reference }}
                                        </div>
                                        <div class="text-sm mt-2 text-gray-600 dark:text-gray-400" v-if="payment.bank_transaction?.btr_description">
                                            Nota: {{ payment.bank_transaction.btr_description }}
                                        </div>
                                        <div v-if="payment.bank_transaction?.btr_diferencial && parseFloat(payment.bank_transaction.btr_diferencial) !== 0" class="mt-2 text-xs font-semibold" :class="parseFloat(payment.bank_transaction.btr_diferencial) > 0 ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400'">
                                            Diferencial: {{ parseFloat(payment.bank_transaction.btr_diferencial) > 0 ? '+' : '' }}${{ Math.abs(parseFloat(payment.bank_transaction.btr_diferencial)).toFixed(4) }} USD
                                            <span class="font-normal text-gray-400">({{ parseFloat(payment.bank_transaction.btr_diferencial) > 0 ? 'Ganancia' : 'Pérdida' }})</span>
                                        </div>
                                    </div>

                                    <div class="flex space-x-2" v-if="payment.apy_type !== 'RETURN_CREDIT'">
                                        <button @click="editPayment(payment.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">
                                            <Pencil class="h-3.5 w-3.5" /> Editar
                                        </button>
                                        <button @click="deletePayment(payment.id)" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs text-red-400 hover:bg-red-900/20 rounded-lg transition-colors">
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end pt-4 border-t border-gray-200 dark:border-zinc-800">
                        <button @click="isPaymentsModalOpen = false" class="px-4 py-2 text-sm border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">Cerrar</button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
