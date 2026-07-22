<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Edit, CheckCircle2, Wallet, User, Calendar, DollarSign, BadgeMinus, BadgeCheck, Loader2, Banknote, ExternalLink, AlertCircle } from 'lucide-vue-next';
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
            { title: 'Detalle', href: '' },
        ],
    },
});

interface BankAccount {
    id: number;
    bank_name: string;
    bank_currency: string;
    bank_balance: string;
}

const props = defineProps<{
    payroll: any;
    bankAccounts: BankAccount[];
    latestRate: any;
    currencies: string[];
    methods: string[];
}>();

const showPayModal = ref(false);

const payForm = useForm({
    bank_account_id: props.bankAccounts.length > 0 ? String(props.bankAccounts[0].id) : '',
    amount: props.payroll.pay_net_salary || '',
    reference: '',
    description: '',
    payment_currency: '',
    payment_rate: '',
    payment_date: '',
    payment_method: '',
});

const originalNetSalary = ref(Number(props.payroll.pay_net_salary) || 0);
const originalCoin = ref(props.payroll.pay_coin || 'USD');

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

watch(() => payForm.payment_currency, (newCurrency) => {
    if (!newCurrency) {
return;
}

    updateExchangeRate();
    recalculateAmount(newCurrency);
});

const openPayModal = () => {
    payForm.payment_currency = originalCoin.value;
    payForm.payment_date = today();
    payForm.payment_method = props.methods.length > 0 ? props.methods[0] : '';
    updateExchangeRate();
    showPayModal.value = true;
};

const closePayModal = () => {
    showPayModal.value = false;
    payForm.clearErrors();
};

const submitPayment = () => {
    payForm.post(`/payrolls/${props.payroll.id}/mark-as-paid`, {
        preserveScroll: true,
        onSuccess: () => closePayModal(),
    });
};

const formatType = (type: string | null | undefined): string => {
    const map: Record<string, string> = {
        'App\\Models\\EmployeeLoan': 'Préstamo',
        'App\\Models\\EmployeeDamage': 'Daño',
    };

    if (!type) return '—';

    return map[type] ?? type.split('\\').pop() ?? type;
};
</script>

<template>
    <Head :title="`Nómina - ${payroll.employee?.emp_name ?? 'Sin empleado'}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/payrolls" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-full transition-colors text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                        <Wallet class="h-8 w-8 text-primary" />
                        Detalle de Nómina
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {{ payroll.employee?.emp_name ?? 'Sin empleado' }}
                    </p>
                </div>
            </div>
            <div class="flex gap-3">
                <Button
                    v-if="payroll.pay_status === 'pendiente'"
                    @click="openPayModal"
                    variant="ghost"
                    size="sm"
                    class="text-green-600 hover:text-green-800 dark:text-green-400"
                >
                    <Banknote class="w-4 h-4" />
                </Button>
                <Link :href="`/payroll-payments/create?employee_id=${payroll.employee?.id}`" v-if="payroll.pay_status === 'pendiente'">
                    <Button class="bg-green-600 hover:bg-green-500 text-white">
                        <ExternalLink class="w-4 h-4 mr-2" />
                        Pagar Nómina
                    </Button>
                </Link>
                <Link :href="`/payrolls/${payroll.id}/edit`" v-if="payroll.pay_status === 'pendiente'">
                    <Button variant="outline">
                        <Edit class="w-4 h-4 mr-2" />
                        Editar
                    </Button>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <User class="w-5 h-5 text-primary" />
                        Información del Empleado
                    </h2>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Nombre:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ payroll.employee?.emp_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Documento:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ payroll.employee?.emp_document ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Cargo:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ payroll.employee?.emp_position ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Moneda Salario:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ payroll.employee?.emp_salary_coin ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-primary" />
                        Período
                    </h2>
                    <div class="grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Inicio:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(payroll.period?.pp_start_date) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Fin:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(payroll.period?.pp_end_date) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Fecha Pago:</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(payroll.period?.pp_payment_date) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <BadgeMinus class="w-5 h-5 text-red-500" />
                        Deducciones
                    </h2>
                    <div v-if="payroll.deductions && payroll.deductions.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Tipo</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Descripción</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700 text-right">Porcentaje</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="deduction in payroll.deductions" :key="deduction.id" class="border-b dark:border-gray-800">
                                    <td class="px-4 py-3 font-medium">{{ formatType(deduction.pde_deductible_type) }}</td>
                                    <td class="px-4 py-3">{{ deduction.deductible?.elo_description ?? deduction.deductible?.eda_description ?? (deduction.deductible ? '-' : 'Registro eliminado') }}</td>
                                    <td class="px-4 py-3 text-right">{{ deduction.pde_percentage }}%</td>
                                    <td class="px-4 py-3 text-right font-semibold text-red-500">-{{ Number(deduction.pde_amount).toLocaleString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else-if="Number(payroll.pay_total_deductions) > 0" class="flex items-start gap-3 p-4 bg-amber-500/10 border border-amber-500/20 rounded-xl">
                        <AlertCircle class="w-5 h-5 text-amber-400 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-amber-300">
                                Hay deducciones por <strong>{{ Number(payroll.pay_total_deductions).toLocaleString() }} {{ payroll.pay_coin }}</strong> registradas manualmente.
                            </p>
                            <p class="text-xs text-amber-400/70 mt-1">
                                Para ver el detalle individual, edita la nómina y aplica los descuentos desde la sección de deducciones.
                            </p>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500">Sin deducciones registradas.</p>
                </div>

                <!-- Payment History -->
                <div v-if="payroll.payments?.length" class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Banknote class="w-5 h-5 text-green-500" />
                        Historial de Pagos
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Fecha</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Monto</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Moneda</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Tasa</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Referencia</th>
                                    <th class="px-4 py-3 border-b dark:border-gray-700">Banco</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="pmt in payroll.payments" :key="pmt.id" class="border-b dark:border-gray-800">
                                    <td class="px-4 py-3">{{ formatDate(pmt.created_at) }}</td>
                                    <td class="px-4 py-3 font-medium">{{ Number(pmt.btr_amount).toLocaleString() }}</td>
                                    <td class="px-4 py-3">{{ pmt.btr_coin }}</td>
                                    <td class="px-4 py-3">{{ pmt.btr_valorcam }}</td>
                                    <td class="px-4 py-3">{{ pmt.btr_reference }}</td>
                                    <td class="px-4 py-3">{{ pmt.bank_account?.bank_name ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <DollarSign class="w-5 h-5 text-primary" />
                        Resumen
                    </h2>
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Salario Base</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ Number(payroll.pay_base_salary).toLocaleString() }} {{ payroll.pay_coin }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tasa de Cambio</span>
                            <span class="font-mono text-xs">{{ payroll.pay_valorcam }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Deducciones</span>
                            <span class="font-semibold text-red-500">-{{ Number(payroll.pay_total_deductions).toLocaleString() }}</span>
                        </div>
                        <div class="pt-4 border-t dark:border-gray-700 flex justify-between">
                            <span class="font-bold text-gray-900 dark:text-white">Sueldo Neto</span>
                            <span class="text-xl font-black text-primary">{{ Number(payroll.pay_net_salary).toLocaleString() }} {{ payroll.pay_coin }}</span>
                        </div>
                        <div class="pt-2 flex justify-between items-center">
                            <span class="text-sm text-gray-500">Estado</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" :class="{
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': payroll.pay_status === 'pendiente',
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': payroll.pay_status === 'pagado',
                            }">
                                <BadgeCheck class="w-3 h-3 mr-1" />
                                {{ payroll.pay_status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <PayModal v-model:open="showPayModal" title="Pagar Nómina" @close="closePayModal">
        <template #summary>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Empleado:</span>
                    <span class="font-medium text-zinc-100">{{ payroll.employee?.emp_name }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Sueldo Neto:</span>
                    <span class="font-medium text-zinc-100">{{ Number(payroll.pay_net_salary).toLocaleString() }} {{ payroll.pay_coin }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-400">Período:</span>
                    <span class="font-medium text-zinc-100">{{ formatDate(payroll.period?.pp_start_date) }} al {{ formatDate(payroll.period?.pp_end_date) }}</span>
                </div>
            </div>
        </template>

        <PaymentForm
            :bank-accounts="bankAccounts"
            :latest-rate="latestRate"
            :currency="payroll.pay_coin"
            :remaining-balance="Number(payroll.pay_net_salary)"
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
