<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Loader2,
    Search,
    Landmark,
    Calculator,
    CreditCard,
    Building2,
    Calendar,
    DollarSign,
    CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import receiptRoutes from '@/routes/receipts';

const props = defineProps<{
    payment?: any;
    customers: any[];
    bankAccounts: any[];
    latestRate: any;
    rates: any[];
    currencies: string[];
    methods: string[];
}>();

const isEditMode = computed(() => !!props.payment);

const pendingReceivables = ref<any[]>([]);
const isLoadingInvoices = ref(false);

const form = useForm({
    payment_date: props.payment?.bank_transaction?.created_at ? props.payment.bank_transaction.created_at.split('T')[0] : new Date().toISOString().split('T')[0],
    customer_id: props.payment?.accounts_receivable?.sale_order?.sls_customer_id?.toString() || props.payment?.accounts_receivable?.sale_order?.customer?.id?.toString() || '',
    bank_account_id: props.payment?.bank_transaction?.btr_bank_account_id?.toString() || '',
    payment_currency: 'USD',
    payment_rate: props.payment?.bank_transaction?.btr_valorcam ? Number(props.payment.bank_transaction.btr_valorcam) : (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
    payment_method: props.payment?.bank_transaction?.btr_type || 'TRANSFERENCIA',
    payment_reference: props.payment?.bank_transaction?.btr_reference || '',
    payment_description: props.payment?.bank_transaction?.btr_description || '',
    payments: [] as { acr_id: number, invoice_number: string, balance: number, currency: string, amount: number }[],
});

const totalToPay = computed(() => {
    return form.payments.reduce((acc, p) => acc + Number(p.amount), 0);
});

const totalInPaymentCurrency = computed(() => {
    return totalToPay.value;
});

const fetchPendingReceivables = async (customerId: string | number) => {
    if (!customerId) {
        pendingReceivables.value = [];
        form.payments = [];

        return;
    }

    isLoadingInvoices.value = true;

    try {
        const response = await fetch(`/api/customers/${customerId}/pending-receivables`);

        if (!response.ok) {
throw new Error('Error al obtener facturas');
}

        const data = await response.json();
        pendingReceivables.value = data;

        form.payments = pendingReceivables.value.map(inv => ({
            acr_id: inv.id,
            invoice_number: inv.acr_sls_number,
            balance: Number(inv.acr_balance),
            currency: inv.sale_order?.sls_coin || 'USD',
            amount: 0
        }));
    } catch (error) {
        console.error('Error fetching receivables:', error);
    } finally {
        isLoadingInvoices.value = false;
    }
};

const selectCustomer = (customer: any) => {
    form.customer_id = customer.id;
    fetchPendingReceivables(customer.id);
};

const getCustomerName = (id: string | number) => {
    const c = props.customers.find(c => c.id == id);

    return c ? c.clt_name : 'Seleccione cliente';
};

const submit = () => {
    if (isEditMode.value) {
        if (form.payments.length === 0 || form.payments[0].amount <= 0 || form.payments[0].amount > form.payments[0].balance) {
            alert('Monto inválido');

            return;
        }

        form.transform((data) => ({
            payment_date: data.payment_date,
            bank_account_id: data.bank_account_id,
            payment_reference: data.payment_reference,
            payment_description: data.payment_description,
            amount: data.payments[0].amount,
        })).put(`/receipts/${props.payment.id}`);

        return;
    }

    if (totalToPay.value <= 0) {
        alert('Debe ingresar al menos un monto a cobrar');

        return;
    }

    form.post(receiptRoutes.store.url(), {
        onSuccess: () => {
            // Success logic
        }
    });
};

// --- LÓGICA DE TASAS ---

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
return null;
}

    const exactRate = props.rates.find(r => r.rate_date === date);

    if (exactRate) {
return exactRate;
}

    const pastRates = props.rates
        // @ts-ignore
        .filter(r => r.rate_date < date)
        // @ts-ignore
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const updateExchangeRate = () => {
    const today = new Date().toISOString().split('T')[0];

    if (form.payment_date === today && props.latestRate) {
        if (form.payment_currency === 'USD') {
            form.payment_rate = 1;
        } else {
            form.payment_rate = form.payment_currency === 'COP'
                ? Number(props.latestRate.rate_cop)
                : Number(props.latestRate.rate_usd);
        }
    } else {
        const selectedRate = getBestRateForDate(form.payment_date);

        if (selectedRate) {
            if (form.payment_currency === 'USD') {
                form.payment_rate = 1;
            } else if (form.payment_currency === 'COP') {
                form.payment_rate = Number(selectedRate.rate_cop);
            } else if (form.payment_currency === 'BS' || form.payment_currency === 'VES') {
                form.payment_rate = Number(selectedRate.rate_usd);
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
    if (isEditMode.value && props.payment) {
        const docCurrency = props.payment.accounts_receivable?.sale_order?.sls_coin || 'USD';
        form.payment_currency = docCurrency;
        form.payment_rate = 1;

        const acr = props.payment.accounts_receivable;
        const currentBalance = Number(acr?.acr_balance || 0);
        const originallyPaid = Number(props.payment.arp_amount || 0);

        form.payments = [{
            acr_id: acr?.id,
            invoice_number: acr?.acr_sls_number,
            balance: currentBalance + originallyPaid,
            currency: docCurrency,
            amount: originallyPaid
        }];
    } else {
        updateExchangeRate();
    }
});

const selectedBankAccount = computed(() => {
    return props.bankAccounts.find(ba => ba.id == form.bank_account_id);
});

const calculateConvertedBalance = (invoicePayment: any) => {
    const balance = invoicePayment.balance;
    const fromCurrency = invoicePayment.currency;
    const toCurrency = form.payment_currency;
    const rate = Number(form.payment_rate);

    if (fromCurrency === toCurrency) {
return balance;
}

    if (fromCurrency === 'COP' && toCurrency === 'USD') {
        const currentRate = getBestRateForDate(form.payment_date);
        const copRate = currentRate ? Number(currentRate.rate_cop) : 1;

        return balance / copRate;
    }

    if (fromCurrency === 'USD' && (toCurrency === 'COP' || toCurrency === 'BS' || toCurrency === 'VES')) {
        return balance * rate;
    }

    return balance;
};

</script>

<template>
    <Head title="Comprobante de Cobro" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Header -->
            <div class="flex items-center justify-between bg-zinc-900/50 backdrop-blur-xl p-6 rounded-2xl border border-zinc-800 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <CheckCircle2 class="w-32 h-32" />
                </div>

                <div class="flex items-center gap-4 relative z-10">
                    <Link href="/accounts-receivable" class="p-2.5 bg-zinc-800/50 hover:bg-zinc-800 rounded-xl transition-all text-zinc-400 hover:text-white border border-zinc-700/50">
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight flex items-center gap-2">
                            {{ isEditMode ? 'Editar Cobro' : 'Comprobante de Cobro' }}
                        </h1>
                        <p class="text-xs text-zinc-500 font-medium tracking-wide first-letter:uppercase">
                            {{ isEditMode ? 'Actualización de cobro registrado' : 'Registro de ingresos y abonos de clientes' }}
                        </p>
                    </div>
                </div>

                <Button
                    @click="submit"
                    :disabled="form.processing || totalToPay <= 0"
                    size="lg"
                    class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold h-12 px-8 rounded-xl shadow-lg shadow-indigo-500/20 active:scale-95 transition-all relative z-10"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    <Save v-else class="w-4 h-4 mr-2" />
                    {{ isEditMode ? 'Actualizar Cobro' : 'Generar Cobro' }}
                </Button>
            </div>

            <!-- Errors -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-500/10 border border-red-500/20 rounded-2xl p-4 flex items-start gap-3">
                <AlertCircle class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                <div>
                    <h4 class="text-sm font-bold text-red-500">Por favor, corrige los siguientes errores:</h4>
                    <ul class="text-xs text-red-400 list-disc list-inside mt-1 space-y-1">
                        <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left Sidebar: Basic Info -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                        <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                            <Landmark class="w-4 h-4" /> Datos del Cliente
                        </div>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <Label class="text-zinc-400 text-xs font-semibold">Cliente</Label>
                                <div v-if="isEditMode" class="h-11 bg-zinc-950/50 border border-zinc-800 rounded-xl px-3 flex items-center text-sm text-zinc-300">
                                    {{ getCustomerName(form.customer_id) }}
                                </div>
                                <SelectorModal v-else
                                    title="Seleccionar Cliente"
                                    description="Busque y seleccione el cliente a cobrar"
                                    :items="customers"
                                    :searchFields="['clt_name']"
                                    :displayFields="[
                                        { label: 'Nombre', key: 'clt_name' }
                                    ]"
                                    :triggerLabel="getCustomerName(form.customer_id)"
                                    :selectedValue="form.customer_id"
                                    @select="selectCustomer"
                                />
                                <span v-if="form.errors.customer_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.customer_id }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold">Fecha de Cobro</Label>
                                    <div class="relative">
                                        <Calendar class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500" />
                                        <Input type="date" v-model="form.payment_date" :class="{'border-red-500': form.errors.payment_date}" class="pl-10 h-11 bg-zinc-950/50 border-zinc-800 text-sm focus:ring-indigo-600 rounded-xl" />
                                    </div>
                                    <span v-if="form.errors.payment_date" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_date }}</span>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold">Moneda de Cobro</Label>
                                    <Select v-model="form.payment_currency" :disabled="isEditMode">
                                        <SelectTrigger class="h-11 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                            <SelectValue placeholder="Moneda" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="c in currencies" :key="c" :value="c">{{ c }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.payment_currency" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_currency }}</span>
                                </div>
                            </div>

                            <div class="p-4 bg-indigo-500/5 rounded-2xl border border-indigo-500/10 space-y-2">
                                <div class="flex justify-between items-center mb-1">
                                    <Label class="text-indigo-400 text-[10px] font-black uppercase tracking-wider">Tasa de Cambio (Manual)</Label>
                                    <Badge variant="outline" class="text-[10px] bg-indigo-500/10 text-indigo-400 border-none">Hoy</Badge>
                                </div>
                                <div class="relative">
                                    <DollarSign class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-indigo-400/50" />
                                        <Input
                                            type="number"
                                            step="0.0001"
                                            v-model="form.payment_rate"
                                            :disabled="isEditMode"
                                            class="pl-10 h-11 bg-zinc-950 border-zinc-800 text-sm font-mono font-bold text-indigo-400 focus:ring-indigo-600 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed"
                                        />
                                </div>
                                <span v-if="form.errors.payment_rate" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_rate }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary Box -->
                    <div class="bg-indigo-600 text-white rounded-3xl p-8 shadow-2xl relative overflow-hidden group">
                        <div class="absolute -right-6 -bottom-6 opacity-10 group-hover:scale-110 transition-transform duration-700">
                            <Calculator class="w-48 h-48" />
                        </div>

                        <div class="relative z-10 space-y-6">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Total a Liquidar</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-xs text-indigo-200">{{ form.payment_currency }}</span>
                                    <span class="text-5xl font-black tabular-nums tracking-tighter">{{ totalInPaymentCurrency.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                                <div class="text-[10px] font-bold text-indigo-100 uppercase tracking-wider">Tasa Aplicada</div>
                                <div class="text-sm font-mono font-bold">{{ Number(form.payment_rate).toFixed(4) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Section: Invoices & Bank Info -->
                <div class="lg:col-span-8 space-y-8">

                    <!-- Pending Invoices Table -->
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 shadow-sm overflow-hidden flex flex-col min-h-[400px]">
                        <div class="p-5 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                                    <CreditCard class="w-5 h-5" />
                                </div>
                                <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400">Facturas Pendientes</h3>
                            </div>
                            <Badge v-if="pendingReceivables.length" variant="outline" class="bg-zinc-800 border-zinc-700 text-zinc-400 text-[10px]">
                                {{ pendingReceivables.length }} facturas encontradas
                            </Badge>
                        </div>

                        <div class="flex-1 overflow-x-auto relative">
                            <div v-if="isLoadingInvoices" class="absolute inset-0 bg-zinc-950/20 backdrop-blur-sm flex items-center justify-center z-10">
                                <Loader2 class="w-8 h-8 animate-spin text-indigo-500" />
                            </div>

                            <div v-if="!form.customer_id" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                                <Search class="w-12 h-12 opacity-20" />
                                <p class="text-sm font-medium italic">Seleccione un cliente para ver sus facturas</p>
                            </div>

                            <div v-else-if="pendingReceivables.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                                <CheckCircle2 class="w-12 h-12 text-green-500/20" />
                                <p class="text-sm font-medium">El cliente no tiene deudas pendientes</p>
                            </div>

                            <table v-else class="w-full text-sm text-left">
                                <thead class="bg-zinc-950/50 text-zinc-500 text-[10px] uppercase font-black border-b border-zinc-800">
                                    <tr>
                                        <th class="px-6 py-5">Factura</th>
                                        <th class="px-6 py-5">Saldo Pendiente</th>
                                        <th class="px-6 py-5 text-center w-48">Monto a Cobrar</th>
                                        <th class="px-6 py-5 text-right w-12 text-zinc-400"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-800">
                                    <tr v-for="invoice in form.payments" :key="invoice.acr_id" class="group hover:bg-zinc-800/30 transition-colors">
                                        <td class="px-6 py-5">
                                            <div class="flex flex-col">
                                                <span class="font-black text-zinc-200">#{{ invoice.invoice_number }}</span>
                                                <span class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Factura de Venta</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex items-center gap-1 font-mono text-zinc-400 font-bold">
                                                <span class="text-[10px] text-zinc-600">{{ form.payment_currency }}</span>
                                                {{ calculateConvertedBalance(invoice).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="relative">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    v-model="invoice.amount"
                                                    @input="invoice.amount = Math.min(Number(invoice.amount), calculateConvertedBalance(invoice))"
                                                    class="text-right h-10 bg-zinc-950 border-zinc-800 group-hover:border-indigo-500/50 rounded-lg text-sm font-black tabular-nums transition-all focus:ring-indigo-600"
                                                />
                                                <button
                                                    @click="invoice.amount = calculateConvertedBalance(invoice)"
                                                    class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] font-black text-indigo-500 hover:text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity"
                                                >
                                                    MÁX
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5">
                                            <div v-if="invoice.amount > 0" class="flex justify-center text-green-500">
                                                <CheckCircle2 class="w-4 h-4" />
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Sources -->
                    <div class="bg-zinc-900 rounded-2xl border border-zinc-800 p-6 space-y-6 shadow-sm">
                        <div class="flex items-center gap-2 text-indigo-400 font-black text-[10px] uppercase tracking-[0.2em] mb-2">
                            <Building2 class="w-4 h-4" /> Origen de Fondos
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Banco y Cuenta</Label>
                                    <Select v-model="form.bank_account_id">
                                        <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl focus:ring-indigo-600">
                                            <SelectValue placeholder="Seleccione cuenta bancaria" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="ba in bankAccounts" :key="ba.id" :value="ba.id.toString()">
                                                <div class="flex flex-col items-start py-0.5">
                                                    <span class="font-bold text-zinc-200">{{ ba.bank_name }}</span>
                                                    <span class="text-[10px] font-mono text-zinc-500">{{ ba.bank_account_number }} ({{ ba.bank_currency }})</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.bank_account_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.bank_account_id }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Tipo de Transacción</Label>
                                    <Select v-model="form.payment_method" :disabled="isEditMode">
                                        <SelectTrigger class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl">
                                            <SelectValue placeholder="Método de cobro" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="m in methods" :key="m" :value="m">{{ m }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.payment_method" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_method }}</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Referencia / Voucher</Label>
                                    <Input v-model="form.payment_reference" placeholder="N° de transacción o referencia" :class="{'border-red-500': form.errors.payment_reference}" class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl" />
                                    <span v-if="form.errors.payment_reference" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_reference }}</span>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-zinc-400 text-xs font-semibold tracking-wide">Observaciones</Label>
                                    <Input v-model="form.payment_description" placeholder="Opcional" :class="{'border-red-500': form.errors.payment_description}" class="h-12 bg-zinc-950/50 border-zinc-800 rounded-xl" />
                                    <span v-if="form.errors.payment_description" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.payment_description }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Account Balance Info -->
                        <div v-if="selectedBankAccount" class="mt-4 p-4 rounded-xl flex items-center gap-4 border" :class="selectedBankAccount.bank_balance >= totalInPaymentCurrency ? 'bg-green-500/5 border-green-500/10' : 'bg-red-500/5 border-red-500/10'">
                            <div :class="selectedBankAccount.bank_balance >= totalInPaymentCurrency ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'" class="p-2.5 rounded-lg">
                                <Landmark class="w-5 h-5" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-black uppercase text-zinc-500">Saldo Disponible en Banco</p>
                                <p class="text-sm font-bold antialiased">{{ selectedBankAccount.formatted_balance }}</p>
                            </div>
                            <div v-if="selectedBankAccount.bank_balance < totalInPaymentCurrency" class="flex items-center gap-1.5 text-red-500 text-[10px] font-black bg-red-500/10 px-3 py-1 rounded-full uppercase">
                                <AlertCircle class="w-3 h-3" /> Fondos Insuficientes
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  margin: 0;
}
input[type=number] {
  appearance: textfield;
  -moz-appearance: textfield;
}

/* Custom transitions */
.v-enter-active,
.v-leave-active {
  transition: opacity 0.5s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.5;
    cursor: pointer;
}
</style>
