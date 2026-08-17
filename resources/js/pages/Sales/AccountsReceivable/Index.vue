<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, FileText, Search, X, Eye, Pencil, Trash2, Calendar, Hash, Download } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/composables/useDate';
import accountsReceivableRoutes from '@/routes/accounts-receivable';
import receiptRoutes from '@/routes/receipts';

interface AccountsReceivable {
    id: number;
    acr_sls_number: string;
    acr_total: string;
    acr_balance: string;
    acr_status: string;
    acr_total_usd: string | null;
    acr_balance_usd: string | null;
    sale_order?: {
        sls_number: string;
        sls_coin: string;
        sls_date: string | null;
        sls_credit_days: number;
        sls_due_date: string | null;
        days_remaining: number;
        transaction_type?: {
            trans_type_description: string;
        };
        customer?: {
            id: number;
            clt_code: string | null;
            clt_cedula: string | null;
            clt_name: string;
        };
    };
    payments?: {
        id: number;
        rec_amount: string;
        rec_diferencial: string;
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
    accountsReceivable: {
        data: AccountsReceivable[];
        links: any[];
    };
    filters: {
        search?: string;
        due?: string;
    };
}>();

const search = ref(props.filters.search || '');
const due = ref(props.filters.due || 'all');

let timeoutId: ReturnType<typeof setTimeout>;

watch([search, due], () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        router.get(accountsReceivableRoutes.index.url({
            query: {
                search: search.value,
                due: due.value === 'all' ? null : due.value,
            },
        }), {}, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

const clearFilters = () => {
    search.value = '';
    due.value = 'all';
};

const dueChips = [
    { value: 'all', label: 'Todas' },
    { value: 'upcoming', label: 'Por vencer' },
    { value: 'overdue', label: 'Vencidas' },
];

const dayBadge = (days: number) => {
    if (days < 0) {
        return {
            class: 'bg-red-900/30 text-red-400',
            label: `${days} días vencido`,
        };
    }

    if (days === 0) {
        return {
            class: 'bg-amber-900/30 text-amber-400',
            label: 'Vence hoy',
        };
    }

    return {
        class: 'bg-emerald-900/30 text-emerald-400',
        label: `${days} días`,
    };
};

const statementUrl = (account: AccountsReceivable) =>
    `/accounts-receivable/client/${account.sale_order?.customer?.id}/pdf`;

const isPaymentsModalOpen = ref(false);
const selectedAccount = ref<AccountsReceivable | null>(null);

const decodeLabel = (label: string) => {
    const textarea = document.createElement('textarea');

    textarea.innerHTML = label;

    return textarea.value;
};

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
            },
        });
    }
};
</script>

<template>
    <Head title="Cuentas por Cobrar - Análisis de Crédito" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/sales" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Cuentas por Cobrar</h1>
                        <p class="text-xs text-zinc-500">Vencimiento de facturas emitidas a crédito (FACV · pendiente / parcial).</p>
                    </div>
                </div>
                <Link :href="receiptRoutes.create.url()" class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20">
                    <CreditCard class="w-4 h-4 mr-2" /> Registrar Cobro
                </Link>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input
                            v-model="search"
                            placeholder="Buscar por factura, cliente o cédula..."
                            class="pl-10 bg-zinc-800 border-zinc-700 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1 rounded-lg bg-zinc-800 p-1 border border-zinc-700">
                            <button
                                v-for="chip in dueChips"
                                :key="chip.value"
                                @click="due = chip.value"
                                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors"
                                :class="due === chip.value ? 'bg-indigo-600 text-white' : 'text-zinc-400 hover:text-zinc-200'"
                            >
                                {{ chip.label }}
                            </button>
                        </div>

                        <button v-if="search || due !== 'all'" @click="clearFilters" class="inline-flex items-center gap-2 px-3 py-2 text-sm text-zinc-400 hover:text-red-400 transition-colors rounded-lg hover:bg-zinc-800">
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
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Cliente</th>
                                <th class="px-4 py-4 text-right">Días Crédito</th>
                                <th class="px-4 py-4 text-center">Días Restantes</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                                <th class="px-4 py-4 w-28 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-if="accountsReceivable.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-zinc-500">
                                    <CreditCard class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                                    <p>No hay facturas a crédito pendientes</p>
                                </td>
                            </tr>
                            <tr v-for="account in accountsReceivable.data" :key="account.id" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs">#{{ account.acr_sls_number }}</td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{ account.sale_order?.transaction_type?.trans_type_description ?? 'FACV' }}
                                </td>
                                <td class="px-4 py-4 text-zinc-400">
                                    {{ formatDate(account.sale_order?.sls_date) }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-zinc-200 font-medium">{{ account.sale_order?.customer?.clt_name ?? 'Desconocido' }}</div>
                                    <div class="text-[10px] text-zinc-500">
                                        <span v-if="account.sale_order?.customer?.clt_code" class="mr-2">Cód: {{ account.sale_order.customer.clt_code }}</span>
                                        <span v-if="account.sale_order?.customer?.clt_cedula">C.I: {{ account.sale_order.customer.clt_cedula }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right text-zinc-400">
                                    {{ account.sale_order?.sls_credit_days ?? 0 }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        v-if="account.sale_order"
                                        class="inline-block px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap"
                                        :class="dayBadge(account.sale_order.days_remaining).class"
                                    >
                                        {{ dayBadge(account.sale_order.days_remaining).label }}
                                    </span>
                                    <span v-else class="text-xs text-zinc-600">—</span>
                                </td>
                                <td class="px-4 py-4 text-right font-mono" :class="parseFloat(account.acr_balance) > 0 ? 'text-red-400' : 'text-green-400'">
                                    {{ parseFloat(account.acr_balance).toFixed(4) }}
                                    <span v-if="account.sale_order?.sls_coin" class="text-[10px] ml-1 opacity-60">{{ account.sale_order.sls_coin }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a
                                            :href="statementUrl(account)"
                                            target="_blank"
                                            rel="noopener"
                                            title="Estado de cuenta del cliente"
                                            class="inline-flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-200"
                                        >
                                            <Download class="h-3.5 w-3.5" /> PDF
                                        </a>
                                        <button @click="viewPayments(account)" class="text-indigo-400 hover:text-indigo-300 text-xs inline-flex items-center gap-1">
                                            <Eye class="h-3.5 w-3.5" /> Cobros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="accountsReceivable.links.length > 3" class="px-6 py-4 border-t border-zinc-800 flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, key) in accountsReceivable.links" :key="key">
                            <div v-if="link.url === null" class="px-4 py-2 text-sm text-zinc-500 border border-zinc-700 rounded cursor-not-allowed">{{ decodeLabel(link.label) }}</div>
                            <Link v-else :href="link.url" class="px-4 py-2 text-sm border border-zinc-700 rounded hover:bg-zinc-800 transition-colors text-zinc-300" :class="{ 'bg-indigo-600 text-white border-indigo-600 font-bold': link.active }">{{ decodeLabel(link.label) }}</Link>
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
                                            ${{ parseFloat(payment.rec_amount).toFixed(4) }}
                                            <span v-if="payment.bank_transaction?.btr_coin" class="text-xs font-normal text-gray-400 ml-1">{{ payment.bank_transaction.btr_coin }}</span>
                                        </h4>
                                        <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                                            <Calendar class="h-4 w-4" />
                                            {{ formatDate(payment.bank_transaction?.created_at ?? payment.created_at) }}
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

                                    <div class="flex space-x-2">
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
