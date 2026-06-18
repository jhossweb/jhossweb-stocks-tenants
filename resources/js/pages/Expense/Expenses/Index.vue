<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { PlusCircle, DollarSign, FileText, Filter, ChevronDown, ChevronUp, Banknote, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PaymentForm from '@/components/payments/PaymentForm.vue';
import PayModal from '@/components/payments/PayModal.vue';
import { Button } from '@/components/ui/button';
import { formatDate, today } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Gastos', href: '/expenses' },
        ],
    },
});

interface Category {
    id: number;
    exc_name: string;
    exc_type: string;
}

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

interface BankTransaction {
    id: number;
    btr_amount: string;
    btr_coin: string;
    btr_valorcam: string;
    btr_reference: string;
    btr_description: string;
    btr_balance: string;
    created_at: string;
    bank_account: BankAccount;
}

interface Expense {
    id: number;
    exp_category_id: number;
    exp_description: string;
    exp_amount: string;
    exp_paid_amount: string;
    exp_remaining_balance: string;
    exp_date: string;
    exp_status: string;
    exp_coin: string;
    exp_valorcam: string;
    exp_payment_date: string | null;
    category?: Category;
    payments?: BankTransaction[];
}

const props = defineProps<{
    expenses: {
        data: Expense[];
        links: any[];
    }
    categories: Category[];
    statusOptions: string[];
    coinOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: any;
    currencies: string[];
    methods: string[];
}>();

const filterCategory = ref('');
const filterStatus = ref('');

const showPayModal = ref(false);
const payingExpense = ref<Expense | null>(null);
const expandedPayments = ref<Set<number>>(new Set());

const payForm = useForm({
    bank_account_id: '',
    amount: '',
    reference: '',
    description: '',
    payment_currency: '',
    payment_rate: '',
    payment_date: '',
    payment_method: '',
});

const originalBalance = ref(0);
const originalCoin = ref('USD');

const updateExchangeRate = () => {
    const coin = payForm.payment_currency || originalCoin.value;
    const rate = resolveRate(coin, props.latestRate);
    payForm.payment_rate = String(rate);
};

const resolveRate = (coin: string, latestRate: any): number => {
    if (!latestRate) {
return 1;
}

    switch (coin) {
        case 'USD': return 1;
        case 'COP': return Number(latestRate.rate_cop) || 1;
        case 'EUR': return Number(latestRate.rate_eur) || 1;
        default: return Number(latestRate.rate_usd) || 1;
    }
};

const recalculateAmount = (newCurrency: string) => {
    if (newCurrency === originalCoin.value) {
        payForm.amount = String(originalBalance.value);

        return;
    }

    const rateOfOriginal = resolveRate(originalCoin.value, props.latestRate);
    const rateOfPayment = Number(payForm.payment_rate) || 1;
    const amountInUSD = originalBalance.value / rateOfOriginal;

    payForm.amount = String(amountInUSD * rateOfPayment);
};

const openPayModal = (expense: Expense) => {
    payingExpense.value = expense;
    originalBalance.value = Number(expense.exp_remaining_balance) || 0;
    originalCoin.value = expense.exp_coin || 'USD';
    payForm.bank_account_id = props.bankAccounts.length > 0 ? String(props.bankAccounts[0].id) : '';
    payForm.amount = String(originalBalance.value);
    payForm.reference = '';
    payForm.description = '';
    payForm.payment_currency = originalCoin.value;
    payForm.payment_date = today();
    payForm.payment_method = props.methods.length > 0 ? props.methods[0] : '';
    updateExchangeRate();
    showPayModal.value = true;
};

watch(() => payForm.payment_currency, (newCurrency) => {
    if (!newCurrency) {
return;
}

    updateExchangeRate();
    recalculateAmount(newCurrency);
});

const closePayModal = () => {
    showPayModal.value = false;
    payingExpense.value = null;
    originalBalance.value = 0;
    originalCoin.value = 'USD';
    payForm.clearErrors();
    payForm.reset();
};

const submitPayment = () => {
    if (!payingExpense.value) {
return;
}

    payForm.post(`/expenses/${payingExpense.value.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => closePayModal(),
    });
};

const togglePayments = (expenseId: number) => {
    if (expandedPayments.value.has(expenseId)) {
        expandedPayments.value.delete(expenseId);
    } else {
        expandedPayments.value.add(expenseId);
    }
};
</script>

<template>
    <Head title="Listado de Gastos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <DollarSign class="h-8 w-8 text-primary" />
                    Gastos
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Registra y administra los gastos operativos.
                </p>
            </div>
            <Link href="/expenses/create">
                <Button class="shadow-md hover:shadow-lg transition-shadow">
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Nuevo Gasto
                </Button>
            </Link>
        </div>

        <div class="flex gap-4 items-center">
            <div class="flex items-center gap-2">
                <Filter class="w-4 h-4 text-gray-400" />
                <select v-model="filterCategory" class="rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                    <option value="">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.exc_name }}</option>
                </select>
            </div>
            <select v-model="filterStatus" class="rounded-md border border-input bg-background px-3 py-1.5 text-sm">
                <option value="">Todos los estados</option>
                <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
            </select>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Categoría</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Descripción</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Monto</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Pagado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Saldo Pendiente</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Fecha</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay gastos registrados</span>
                                </div>
                            </td>
                        </tr>
                        <template v-for="expense in expenses.data" :key="expense.id">
                            <tr class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ expense.category?.exc_name ?? 'Sin categoría' }}
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate">
                                    {{ expense.exp_description }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                    {{ Number(expense.exp_amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">
                                    {{ Number(expense.exp_paid_amount).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 font-semibold" :class="Number(expense.exp_remaining_balance) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                                    {{ Number(expense.exp_remaining_balance).toLocaleString() }}
                                </td>
                                <td class="px-6 py-4 font-mono text-xs">
                                    {{ expense.exp_coin }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ formatDate(expense.exp_date) }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': expense.exp_status === 'pendiente',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': expense.exp_status === 'pagado',
                                    }">
                                        {{ expense.exp_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="`/expenses/${expense.id}/edit`">
                                            <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                                Editar
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="expense.exp_status === 'pendiente'"
                                            variant="outline"
                                            size="sm"
                                            @click="openPayModal(expense)"
                                            class="text-green-600 border-green-300 hover:bg-green-50 dark:text-green-400 dark:border-green-700 dark:hover:bg-green-900/20"
                                        >
                                            <Banknote class="w-3.5 h-3.5 mr-1" />
                                            Pagar
                                        </Button>
                                        <Button
                                            v-if="expense.payments?.length"
                                            variant="ghost"
                                            size="sm"
                                            @click="togglePayments(expense.id)"
                                        >
                                            <ChevronDown v-if="!expandedPayments.has(expense.id)" class="w-4 h-4" />
                                            <ChevronUp v-else class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="expandedPayments.has(expense.id) && expense.payments?.length">
                                <td colspan="9" class="px-6 py-2 bg-gray-50 dark:bg-gray-800/50">
                                    <div class="text-xs text-gray-500 mb-1 font-medium">Historial de Pagos</div>
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-400 border-b dark:border-gray-700">
                                                <th class="px-3 py-1 text-left">Fecha</th>
                                                <th class="px-3 py-1 text-left">Monto</th>
                                                <th class="px-3 py-1 text-left">Moneda</th>
                                                <th class="px-3 py-1 text-left">Tasa</th>
                                                <th class="px-3 py-1 text-left">Referencia</th>
                                                <th class="px-3 py-1 text-left">Banco</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="pmt in expense.payments" :key="pmt.id" class="border-b dark:border-gray-700/50">
                                                <td class="px-3 py-1.5">{{ formatDate(pmt.created_at) }}</td>
                                                <td class="px-3 py-1.5 font-medium">{{ Number(pmt.btr_amount).toLocaleString() }}</td>
                                                <td class="px-3 py-1.5">{{ pmt.btr_coin }}</td>
                                                <td class="px-3 py-1.5">{{ pmt.btr_valorcam }}</td>
                                                <td class="px-3 py-1.5">{{ pmt.btr_reference }}</td>
                                                <td class="px-3 py-1.5">{{ pmt.bank_account?.bank_name ?? '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="expenses.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in expenses.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <PayModal v-model:open="showPayModal" title="Registrar Pago" @close="closePayModal">
        <template #summary>
            <div v-if="payingExpense" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Gasto:</span>
                    <span class="font-medium text-zinc-100 text-right">{{ payingExpense.exp_description }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Monto total:</span>
                    <span class="font-medium text-zinc-100">{{ Number(payingExpense.exp_amount).toLocaleString() }} {{ payingExpense.exp_coin }}</span>
                </div>
                <div class="flex justify-between text-sm pt-1 border-t border-zinc-800">
                    <span class="text-zinc-400">Saldo pendiente:</span>
                    <span class="font-semibold text-amber-400">{{ Number(payingExpense.exp_remaining_balance).toLocaleString() }} {{ payingExpense.exp_coin }}</span>
                </div>
            </div>
        </template>

        <PaymentForm
            :bank-accounts="bankAccounts"
            :latest-rate="latestRate"
            :currency="payingExpense?.exp_coin ?? 'USD'"
            :remaining-balance="Number(payingExpense?.exp_remaining_balance ?? 0)"
            :form="payForm"
            :errors="payForm.errors"
            :processing="payForm.processing"
            show-rate
            show-date
            show-method
            :currencies="currencies"
            :methods="methods"
        />

        <template #actions>
            <Button
                @click="submitPayment"
                :disabled="payForm.processing"
                class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold"
            >
                <Loader2 v-if="payForm.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ payForm.processing ? 'Procesando...' : 'Registrar Pago' }}
            </Button>
        </template>
    </PayModal>
</template>
