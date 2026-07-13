<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, FileText, Wallet, CheckCircle2, Banknote, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import PaymentForm from '@/components/payments/PaymentForm.vue';
import PayModal from '@/components/payments/PayModal.vue';
import { Button } from '@/components/ui/button';
import { formatDate, today } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Nóminas', href: '/payrolls' },
        ],
    },
});

interface Employee {
    id: number;
    emp_name: string;
}

interface Period {
    id: number;
    pp_start_date: string;
    pp_end_date: string;
}

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

interface Payroll {
    id: number;
    pay_employee_id: number;
    pay_period_id: number;
    pay_base_salary: string;
    pay_coin: string;
    pay_total_deductions: string;
    pay_net_salary: string;
    pay_status: string;
    employee?: Employee;
    period?: Period;
}

const props = defineProps<{
    payrolls: {
        data: Payroll[];
        links: any[];
    }
    statusOptions: string[];
    bankAccounts: BankAccount[];
    latestRate: any;
    currencies: string[];
    methods: string[];
}>();

const showPayModal = ref(false);
const payingPayroll = ref<Payroll | null>(null);

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

const originalNetSalary = ref(0);
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
        payForm.amount = String(originalNetSalary.value);

        return;
    }

    const rateOfOriginal = resolveRate(originalCoin.value, props.latestRate);
    const rateOfPayment = Number(payForm.payment_rate) || 1;
    const amountInUSD = originalNetSalary.value / rateOfOriginal;

    payForm.amount = String(amountInUSD * rateOfPayment);
};

const openPayModal = (payroll: Payroll) => {
    payingPayroll.value = payroll;
    originalNetSalary.value = Number(payroll.pay_net_salary) || 0;
    originalCoin.value = payroll.pay_coin || 'USD';
    payForm.bank_account_id = props.bankAccounts.length > 0 ? String(props.bankAccounts[0].id) : '';
    payForm.amount = String(originalNetSalary.value);
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
    payingPayroll.value = null;
    originalNetSalary.value = 0;
    originalCoin.value = 'USD';
    payForm.clearErrors();
    payForm.reset();
};

const submitPayment = () => {
    if (!payingPayroll.value) {
return;
}

    payForm.post(`/payrolls/${payingPayroll.value.id}/mark-as-paid`, {
        preserveScroll: true,
        onSuccess: () => closePayModal(),
    });
};
</script>

<template>
    <Head title="Nóminas" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Wallet class="h-8 w-8 text-primary" />
                    Nóminas
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Gestiona las nóminas generadas por período.
                </p>
            </div>
            <div class="flex gap-3">
                <Link href="/payroll-payments/create">
                    <Button variant="default" class="bg-green-600 hover:bg-green-500 text-white shadow-sm">
                        <Banknote class="mr-2 h-4 w-4" />
                        Pago de Nómina
                    </Button>
                </Link>
                <Link href="/payrolls/create">
                    <Button variant="outline" class="shadow-sm">
                        <PlusCircle class="mr-2 h-4 w-4" />
                        Nueva Nómina
                    </Button>
                </Link>
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden flex-1 flex flex-col">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Empleado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Período</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Salario Base</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Moneda</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Deducciones</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Neto</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Estado</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="payrolls.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-3">
                                    <FileText class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    <span class="text-base font-medium">No hay nóminas registradas</span>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="payroll in payrolls.data" :key="payroll.id" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ payroll.employee?.emp_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-xs">
                                {{ formatDate(payroll.period?.pp_start_date) }} al {{ formatDate(payroll.period?.pp_end_date) }}
                            </td>
                            <td class="px-6 py-4 font-semibold">
                                {{ Number(payroll.pay_base_salary).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs">
                                {{ payroll.pay_coin }}
                            </td>
                            <td class="px-6 py-4 text-red-500 font-semibold">
                                -{{ Number(payroll.pay_total_deductions).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                {{ Number(payroll.pay_net_salary).toLocaleString() }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="{
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': payroll.pay_status === 'pendiente',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': payroll.pay_status === 'pagado',
                                }">
                                    {{ payroll.pay_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/payrolls/${payroll.id}`">
                                        <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                            Ver
                                        </Button>
                                    </Link>
                                    <Link :href="`/payrolls/${payroll.id}/edit`" v-if="payroll.pay_status === 'pendiente'">
                                        <Button variant="ghost" size="sm" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                                            Editar
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="payroll.pay_status === 'pendiente'"
                                        variant="ghost"
                                        size="sm"
                                        @click="openPayModal(payroll)"
                                        class="text-green-600 hover:text-green-800 dark:text-green-400"
                                    >
                                        <Banknote class="w-4 h-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="payrolls.links.length > 3" class="px-6 py-4 border-t dark:border-gray-800 flex justify-center bg-gray-50 dark:bg-gray-900">
                <div class="flex gap-1">
                    <template v-for="(link, key) in payrolls.links" :key="key">
                        <div v-if="link.url === null" class="px-4 py-2 text-sm text-gray-400 border rounded cursor-not-allowed" v-html="link.label"></div>
                        <Link v-else :href="link.url" class="px-4 py-2 text-sm border rounded hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors" :class="{ 'bg-primary text-primary-foreground font-bold': link.active }" v-html="link.label"></Link>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <PayModal v-model:open="showPayModal" title="Pagar Nómina" @close="closePayModal">
        <template #summary>
            <div v-if="payingPayroll" class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Empleado:</span>
                    <span class="font-medium text-zinc-100">{{ payingPayroll.employee?.emp_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Sueldo Neto:</span>
                    <span class="font-medium text-zinc-100">{{ Number(payingPayroll.pay_net_salary).toLocaleString() }} {{ payingPayroll.pay_coin }}</span>
                </div>
            </div>
        </template>

        <PaymentForm
            :bank-accounts="bankAccounts"
            :latest-rate="latestRate"
            :currency="payingPayroll?.pay_coin ?? 'USD'"
            :remaining-balance="Number(payingPayroll?.pay_net_salary ?? 0)"
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
                {{ payForm.processing ? 'Procesando...' : 'Confirmar Pago' }}
            </Button>
        </template>
    </PayModal>
</template>
