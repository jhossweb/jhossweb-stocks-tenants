<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Loader2,
    Search,
    CheckCircle2,
    CreditCard,
    Calendar,
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import BasePaymentPage from '@/components/payments/BasePaymentPage.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Badge as UIBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import { formatDate, today } from '@/composables/useDate';

const props = defineProps<{
    employees: any[];
    bankAccounts: any[];
    latestRate: any;
    rates: any[];
    currencies: string[];
    methods: string[];
    employeeId?: string;
}>();

const { convertCurrency } = useCurrencyConversion();

const pendingPayrolls = ref<any[]>([]);
const isLoadingPayrolls = ref(false);

const form = useForm({
    payment_date: today(),
    employee_id: '',
    bank_account_id: '',
    payment_currency: 'USD',
    payment_rate: props.latestRate ? Number(props.latestRate.rate_usd) : 1,
    payment_method: 'TRANSFERENCIA',
    payment_reference: '',
    payment_description: '',
    amount: 0,
    payments: [] as { pay_id: number, period_name: string, net_salary: number, balance: number, currency: string, amount: number }[],
});

const totalToPay = computed(() => {
    return form.payments.reduce((acc, p) => acc + Number(p.amount), 0);
});

const totalInPaymentCurrency = computed(() => {
    return totalToPay.value;
});

const originalTotal = computed(() => {
    return form.payments.reduce((acc, p) => acc + Number(p.balance), 0);
});

const originalCurrency = computed(() => {
    const currencies = [...new Set(form.payments.map(p => p.currency))];

    return currencies.length === 1 ? currencies[0] : undefined;
});

const fetchPendingPayrolls = async (employeeId: string | number) => {
    if (!employeeId) {
        pendingPayrolls.value = [];
        form.payments = [];

        return;
    }

    isLoadingPayrolls.value = true;

    try {
        const response = await fetch(`/api/employees/${employeeId}/pending-payrolls`);

        if (!response.ok) {
throw new Error('Error al obtener nóminas pendientes');
}
        
        const data = await response.json();
        pendingPayrolls.value = data;
        
        form.payments = pendingPayrolls.value.map(p => ({
            pay_id: p.id,
            period_name: p.period ? `${formatDate(p.period.pp_start_date)} - ${formatDate(p.period.pp_end_date)}` : `Período #${p.pay_period_id}`,
            net_salary: Number(p.pay_net_salary),
            balance: Number(p.pay_net_salary),
            currency: p.pay_coin || 'USD',
            amount: 0
        }));
    } catch (error) {
        console.error('Error fetching payrolls:', error);
    } finally {
        isLoadingPayrolls.value = false;
    }
};

const selectEmployee = (employee: any) => {
    form.employee_id = employee.id;
    fetchPendingPayrolls(employee.id);
};

const getEmployeeName = (id: string | number) => {
    const e = props.employees.find(e => e.id == id);

    return e ? e.emp_name : 'Seleccione empleado';
};

const submit = () => {
    if (totalToPay.value <= 0) {
        alert('Debe ingresar al menos un monto a pagar');

        return;
    }
    
    form.transform((data) => ({
        payment_date: data.payment_date,
        employee_id: data.employee_id,
        bank_account_id: data.bank_account_id,
        payment_currency: data.payment_currency,
        payment_rate: data.payment_rate,
        payment_method: data.payment_method,
        payment_reference: data.payment_reference,
        payment_description: data.payment_description,
        payments: data.payments.filter(p => Number(p.amount) > 0).map(p => ({
            pay_id: p.pay_id,
            amount: p.amount
        })),
    })).post('/payroll-payments', {
        onError: (errors) => {
            console.error(errors);
        }
    });
};

// --- TASA DE CAMBIO ---

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
return null;
}

    const exactRate = props.rates.find(r => r.rate_date === date);
    
    if (exactRate) {
return exactRate;
}

    const pastRates = props.rates
        .filter(r => r.rate_date < date)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0]; 
};

const updateExchangeRate = () => {
    if (form.payment_date === today() && props.latestRate) {
        if (form.payment_currency === 'USD' || form.payment_currency === 'BS' || form.payment_currency === 'VES') {
            form.payment_rate = Number(props.latestRate.rate_usd);
        } else if (form.payment_currency === 'COP') {
            form.payment_rate = Number(props.latestRate.rate_cop);
        } else if (form.payment_currency === 'EUR') {
            form.payment_rate = Number(props.latestRate.rate_eur);
        }
    } else {
        const selectedRate = getBestRateForDate(form.payment_date);

        if (selectedRate) {
            if (form.payment_currency === 'USD' || form.payment_currency === 'BS' || form.payment_currency === 'VES') {
                form.payment_rate = Number(selectedRate.rate_usd);
            } else if (form.payment_currency === 'COP') {
                form.payment_rate = Number(selectedRate.rate_cop);
            } else if (form.payment_currency === 'EUR') {
                form.payment_rate = Number(selectedRate.rate_eur);
            }
        }
    }
};

watch(() => [form.payment_currency, form.payment_date], () => {
    updateExchangeRate();
});

onMounted(() => {
    if (props.employeeId) {
        const emp = props.employees.find(e => e.id == props.employeeId);

        if (emp) {
            selectEmployee(emp);
        }
    }

    updateExchangeRate();
});

const selectedBankAccount = computed(() => {
    return props.bankAccounts.find(ba => ba.id == form.bank_account_id);
});

const calculateConvertedBalance = (payment: any) => {
    const currentRate = getBestRateForDate(form.payment_date) || props.latestRate || {};

    return convertCurrency(
        payment.balance,
        payment.currency,
        form.payment_currency,
        currentRate,
    );
};

</script>

<template>
    <Head title="Pago de Nómina" />

    <BasePaymentPage
        title="Pago de Nómina"
        subtitle="Registro de pagos de nómina a empleados"
        back-route="/payrolls"
        :form="form"
        :errors="form.errors"
        :processing="form.processing"
        submit-label="Generar Pago"
        :bank-accounts="bankAccounts"
        :latest-rate="latestRate"
        :currencies="currencies"
        :methods="methods"
        total-label="Total a Liquidar"
        :total-amount="totalInPaymentCurrency"
        :total-currency="form.payment_currency"
        :original-total="originalTotal"
        :original-currency="originalCurrency"
        @submit="submit"
    >
        <template #entity-selector>
            <div class="space-y-2">
                <Label class="text-zinc-400 text-xs font-semibold">Empleado</Label>
                <SelectorModal
                    title="Seleccionar Empleado"
                    description="Busque y seleccione el empleado a pagar"
                    :items="employees"
                    :searchFields="['emp_name', 'emp_document']"
                    :displayFields="[
                        { label: 'Nombre', key: 'emp_name' },
                        { label: 'Documento', key: 'emp_document' }
                    ]"
                    :triggerLabel="getEmployeeName(form.employee_id)"
                    :selectedValue="form.employee_id"
                    @select="selectEmployee"
                />
                <span v-if="form.errors.employee_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.employee_id }}</span>
            </div>
        </template>

        <template #items-table>
            <div class="bg-zinc-900 rounded-2xl border border-zinc-800 shadow-sm overflow-hidden flex flex-col min-h-[400px]">
                <div class="p-5 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                            <CreditCard class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400">Nóminas Pendientes</h3>
                    </div>
                    <UIBadge v-if="pendingPayrolls.length" variant="outline" class="bg-zinc-800 border-zinc-700 text-zinc-400 text-[10px]">
                        {{ pendingPayrolls.length }} nóminas encontradas
                    </UIBadge>
                </div>

                <div class="flex-1 overflow-x-auto relative">
                    <div v-if="isLoadingPayrolls" class="absolute inset-0 bg-zinc-950/20 backdrop-blur-sm flex items-center justify-center z-10">
                        <Loader2 class="w-8 h-8 animate-spin text-indigo-500" />
                    </div>

                    <div v-if="!form.employee_id" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                        <Search class="w-12 h-12 opacity-20" />
                        <p class="text-sm font-medium italic">Seleccione un empleado para ver sus nóminas pendientes</p>
                    </div>

                    <div v-else-if="pendingPayrolls.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                        <CheckCircle2 class="w-12 h-12 text-green-500/20" />
                        <p class="text-sm font-medium">El empleado no tiene nóminas pendientes</p>
                    </div>

                    <table v-else class="w-full text-sm text-left">
                        <thead class="bg-zinc-950/50 text-zinc-500 text-[10px] uppercase font-black border-b border-zinc-800">
                            <tr>
                                <th class="px-6 py-5">Período</th>
                                <th class="px-6 py-5">Salario Neto</th>
                                <th class="px-6 py-5 text-center w-48">Monto a Pagar</th>
                                <th class="px-6 py-5 text-right w-12 text-zinc-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="(payrollItem, index) in form.payments" :key="payrollItem.pay_id" class="group hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="w-4 h-4 text-indigo-500/50" />
                                        <span class="font-black text-zinc-200">{{ payrollItem.period_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1 font-mono text-zinc-400 font-bold">
                                            <span class="text-[10px] text-zinc-600">{{ payrollItem.currency }}</span>
                                            {{ Number(payrollItem.balance).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                        </div>
                                        <div
                                            v-if="payrollItem.currency !== form.payment_currency"
                                            class="flex items-center gap-1 font-mono text-[11px] text-amber-400 font-bold"
                                        >
                                            <span class="text-[10px] text-amber-600">≈ {{ form.payment_currency }}</span>
                                            {{ calculateConvertedBalance(payrollItem).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="relative">
                                        <Input
                                            type="number"
                                            step="0.0001"
                                            v-model="payrollItem.amount"
                                            @input="payrollItem.amount = Math.min(Number(payrollItem.amount), calculateConvertedBalance(payrollItem))"
                                            class="text-right h-10 bg-zinc-950 border-zinc-800 group-hover:border-indigo-500/50 rounded-lg text-sm font-black tabular-nums transition-all focus:ring-indigo-600"
                                        />
                                        <button
                                            @click="payrollItem.amount = calculateConvertedBalance(payrollItem)"
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] font-black text-indigo-500 hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"
                                        >
                                            MÁX
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div v-if="payrollItem.amount > 0" class="flex justify-center text-green-500">
                                        <CheckCircle2 class="w-4 h-4" />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </BasePaymentPage>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    appearance: textfield;
    -moz-appearance: textfield;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
