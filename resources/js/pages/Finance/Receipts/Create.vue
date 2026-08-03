<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Loader2,
    Search,
    Landmark,
    CreditCard,
    Building2,
    CheckCircle2,
    AlertCircle,
    Repeat,
    Calculator,
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
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
const page = usePage();

const { convertCurrency } = useCurrencyConversion();

const pendingReceivables = ref<any[]>([]);
const isLoadingInvoices = ref(false);

const form = useForm({
    payment_date: props.payment?.bank_transaction?.created_at ? props.payment.bank_transaction.created_at.split('T')[0] : new Date().toISOString().split('T')[0],
    customer_id: props.payment?.accounts_receivable?.sale_order?.sls_customer_id?.toString() || props.payment?.accounts_receivable?.sale_order?.customer?.id?.toString() || '',
    bank_account_id: props.payment?.bank_transaction?.btr_bank_account_id?.toString() || '',
    payment_currency: props.payment?.bank_transaction?.btr_coin || 'USD',
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

const originalTotal = computed(() => {
    return form.payments.reduce((acc, p) => acc + Number(p.balance), 0);
});

const originalCurrency = computed(() => {
    const currencies = [...new Set(form.payments.map(p => p.currency))];

    return currencies.length === 1 ? currencies[0] : undefined;
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

    console.log('submit called', { totalToPay: totalToPay.value, payments: form.payments });

    form
        .transform((data) => ({
            ...data,
            payments: data.payments.filter((p: any) => Number(p.amount) > 0),
        }))
        .post(receiptRoutes.store.url(), {
            onSuccess: () => {
            },
            onError: (errors) => {
                console.error('Error al guardar cobro:', errors);
            },
        });
};

// --- LÓGICA DE TASAS ---

const normalizeDate = (d: string) => d.substring(0, 10);

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
        return null;
    }

    const normalized = normalizeDate(date);
    const exactRate = props.rates.find(r => normalizeDate(r.rate_date) === normalized);

    if (exactRate) {
        return exactRate;
    }

    const pastRates = props.rates
        .filter(r => normalizeDate(r.rate_date) < normalized)
        .sort((a, b) => normalizeDate(b.rate_date).localeCompare(normalizeDate(a.rate_date)));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const isBS = (c: string) => c === 'BS' || c === 'VES';

const resolvePaymentRate = (docCurrency: string, paymentCoin: string, rateRecord: any): number => {
    const e = docCurrency?.toUpperCase() || '';
    const p = paymentCoin?.toUpperCase() || '';

    if (!p || e === p) {
        if (e === 'USD') {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'COP') {
            return Number(rateRecord.rate_cop) || 1;
        }

        if (isBS(e)) {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'EUR') {
            return Number(rateRecord.rate_eur) || 1;
        }

        return Number(rateRecord.rate_usd) || 1;
    }

    if ((e === 'COP' && isBS(p)) || (isBS(e) && p === 'COP')) {
        return Number(rateRecord.rate_cop_bs) || 1;
    }

    if (p === 'USD') {
        if (e === 'COP') {
            return Number(rateRecord.rate_cop) || 1;
        }

        if (isBS(e)) {
            return Number(rateRecord.rate_usd) || 1;
        }

        if (e === 'EUR') {
            return Number(rateRecord.rate_eur) || 1;
        }

        return Number(rateRecord.rate_usd) || 1;
    }

    if (p === 'COP') {
        return Number(rateRecord.rate_cop) || 1;
    }

    if (isBS(p)) {
        return Number(rateRecord.rate_usd) || 1;
    }

    if (p === 'EUR') {
        return Number(rateRecord.rate_eur) || 1;
    }

    return Number(rateRecord.rate_usd) || 1;
};

const updateExchangeRate = () => {
    const selectedRate = getBestRateForDate(form.payment_date);

    if (!selectedRate) {
        return;
    }

    const docCurrency = originalCurrency.value || 'USD';
    form.payment_rate = resolvePaymentRate(docCurrency, form.payment_currency, selectedRate);
};

watch(() => [form.payment_currency, form.payment_date], ([newCurrency, oldCurrency]) => {
    if (newCurrency !== oldCurrency) {
        const isInFiltered = filteredBankAccounts.value.some(ba => ba.id == form.bank_account_id);

        if (!isInFiltered) {
            form.bank_account_id = '';
        }
    }

    updateExchangeRate();

    form.payments.forEach(invoice => {
        const max = calculateConvertedBalance(invoice);

        if (Number(invoice.amount) > max) {
            invoice.amount = max;
        }
    });
});

onMounted(() => {
    if (isEditMode.value && props.payment) {
        const docCurrency = props.payment.accounts_receivable?.sale_order?.sls_coin || 'USD';
        form.payment_currency = docCurrency;

        if (docCurrency === 'USD' || docCurrency === 'BS' || docCurrency === 'VES') {
            form.payment_rate = Number(props.latestRate?.rate_usd || 1);
        } else if (docCurrency === 'COP') {
            form.payment_rate = Number(props.latestRate?.rate_cop || 1);
        } else if (docCurrency === 'EUR') {
            form.payment_rate = Number(props.latestRate?.rate_eur || 1);
        } else {
            form.payment_rate = 1;
        }

        const acr = props.payment.accounts_receivable;
        const currentBalance = Number(acr?.acr_balance || 0);
        const originallyPaid = Number(props.payment.rec_amount || 0);

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

const filteredBankAccounts = computed(() =>
    props.bankAccounts.filter(ba => ba.bank_currency === form.payment_currency)
);

const selectedBankAccount = computed(() => {
    return filteredBankAccounts.value.find(ba => ba.id == form.bank_account_id);
});

const estimatedDiferencial = computed(() => {
    if (form.payments.length === 0 || totalToPay.value <= 0) {
return 0;
}

    const payRate = Number(form.payment_rate) || 1;
    const docRate = Number(props.latestRate?.rate_usd) || 1;
    let totalDif = 0;

    for (const invoice of form.payments) {
        if (!invoice.amount || invoice.amount <= 0) {
continue;
}

        const payCoin = form.payment_currency;

        let paidInUsd: number;

        if (payCoin === 'USD') {
            paidInUsd = invoice.amount;
        } else {
            paidInUsd = invoice.amount / payRate;
        }

        let docValueInUsd: number;

        if (invoice.currency === 'USD') {
            docValueInUsd = invoice.amount;
        } else {
            docValueInUsd = invoice.amount / docRate;
        }

        totalDif += paidInUsd - docValueInUsd;
    }

    return totalDif;
});

const calculateConvertedBalance = (invoicePayment: any) => {
    const currentRate = getBestRateForDate(form.payment_date) || props.latestRate || {};

    return convertCurrency(
        invoicePayment.balance,
        invoicePayment.currency,
        form.payment_currency,
        currentRate,
    );
};

</script>

<template>
    <Head title="Comprobante de Cobro" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm">
            <div class="flex items-center gap-4">
                <Link
                    href="/accounts-receivable"
                    class="text-muted-foreground hover:text-foreground rounded-full p-2 transition-colors hover:bg-accent"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">
                        {{ isEditMode ? 'Editar Cobro' : 'Comprobante de Cobro' }}
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        {{ isEditMode ? 'Actualización de cobro registrado' : 'Registro de ingresos y abonos de clientes' }}
                    </p>
                </div>
            </div>
            <Button
                @click="submit"
                :disabled="form.processing || totalToPay <= 0"
                size="lg"
                class="bg-primary text-primary-foreground hover:bg-primary/90 font-bold h-12 px-8 rounded-xl shadow-sm transition-all"
            >
                <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                <Save v-else class="w-4 h-4 mr-2" />
                {{ isEditMode ? 'Actualizar Cobro' : 'Generar Cobro' }}
            </Button>
        </div>

        <div v-if="Object.keys(form.errors).length > 0 || Object.keys(page.props.errors).length > 0" class="rounded-xl border border-destructive/50 bg-destructive/10 p-4">
            <p class="mb-1 text-sm font-bold text-destructive">
                Por favor, corrige los siguientes errores:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in form.errors" :key="'form-'+key">{{ error }}</li>
                <li v-for="(error, key) in page.props.errors" :key="'page-'+key">{{ error }}</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Landmark class="size-4 text-primary" />
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Datos del Cliente</span>
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Cliente</label>
                            <div v-if="isEditMode" class="flex h-10 items-center rounded-lg border bg-muted/30 px-3 text-sm">
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
                            <span v-if="form.errors.customer_id" class="mt-1 text-xs text-destructive">{{ form.errors.customer_id }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha de Cobro</label>
                                <Input type="date" v-model="form.payment_date" :class="{'border-destructive': form.errors.payment_date}" />
                                <span v-if="form.errors.payment_date" class="mt-1 text-xs text-destructive">{{ form.errors.payment_date }}</span>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Moneda de Cobro</label>
                                <Select v-model="form.payment_currency" :disabled="isEditMode">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Moneda" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="c in currencies" :key="c" :value="c">{{ c }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span v-if="form.errors.payment_currency" class="mt-1 text-xs text-destructive">{{ form.errors.payment_currency }}</span>
                            </div>
                        </div>

                        <div class="rounded-lg border bg-muted/30 p-4">
                            <label class="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Tasa {{ originalCurrency || 'USD' }} / {{ form.payment_currency }}</label>
                            <Input
                                type="number"
                                step="0.0001"
                                v-model="form.payment_rate"
                                :disabled="isEditMode"
                                class="font-mono text-sm font-bold disabled:opacity-50 disabled:cursor-not-allowed"
                            />
                            <span v-if="form.errors.payment_rate" class="mt-1 text-xs text-destructive">{{ form.errors.payment_rate }}</span>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg">
                    <Calculator class="text-primary-foreground/10 absolute -bottom-4 -right-4 size-24" />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>Total a Liquidar</span>
                            <span>{{ totalInPaymentCurrency.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>Tasa Aplicada</span>
                            <span class="font-mono">{{ Number(form.payment_rate).toFixed(4) }}</span>
                        </div>

                        <div
                            v-if="originalTotal > 0 && originalCurrency && originalCurrency !== form.payment_currency"
                            class="flex flex-col gap-2 border-t border-primary-foreground/10 pt-3"
                        >
                            <div class="flex items-center gap-2 text-xs">
                                <Repeat class="size-3.5" /> Conversión de Moneda
                            </div>
                            <div class="flex justify-between">
                                <span>Total a pagar</span>
                                <span class="font-bold">{{ totalInPaymentCurrency.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }} {{ form.payment_currency }}</span>
                            </div>
                            <div class="text-xs text-right text-primary-foreground/70">
                                (equivalente a {{ originalTotal.toLocaleString('en-US', { minimumFractionDigits: 4 }) }} {{ originalCurrency }})
                            </div>
                        </div>

                        <div
                            v-if="estimatedDiferencial !== 0 && form.payment_currency !== 'USD'"
                            class="flex flex-col gap-2 border-t border-primary-foreground/10 pt-3"
                        >
                            <div class="flex justify-between">
                                <span class="text-xs">Diferencial Cambiario</span>
                                <span
                                    class="text-sm font-bold"
                                    :class="estimatedDiferencial > 0 ? 'text-green-300' : 'text-orange-300'"
                                >
                                    {{ estimatedDiferencial > 0 ? '+' : '' }}${{ Math.abs(estimatedDiferencial).toFixed(4) }} USD
                                </span>
                            </div>
                            <div class="text-xs text-right text-primary-foreground/70">
                                {{ estimatedDiferencial > 0 ? 'Ganancia por diferencia de tasas' : 'Pérdida por diferencia de tasas' }}
                            </div>
                        </div>

                        <div class="flex items-end justify-between border-t border-primary-foreground/10 pt-3">
                            <span class="text-sm font-bold uppercase">Total {{ form.payment_currency }}</span>
                            <span class="text-3xl font-black tabular-nums">{{ totalInPaymentCurrency.toLocaleString('en-US', { minimumFractionDigits: 4, maximumFractionDigits: 4 }) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:col-span-8">
                <div class="overflow-hidden rounded-xl border bg-card shadow-sm flex flex-col min-h-[400px]">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                <CreditCard class="size-4" />
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Facturas Pendientes</span>
                        </div>
                        <Badge v-if="pendingReceivables.length" variant="outline" class="bg-muted/30 text-muted-foreground text-[10px]">
                            {{ pendingReceivables.length }} facturas encontradas
                        </Badge>
                    </div>

                    <div class="relative flex-1 overflow-x-auto">
                        <div v-if="isLoadingInvoices" class="absolute inset-0 flex items-center justify-center bg-background/20 backdrop-blur-sm z-10">
                            <Loader2 class="size-8 animate-spin text-primary" />
                        </div>

                        <div v-if="!form.customer_id" class="absolute inset-0 flex flex-col items-center justify-center text-muted-foreground gap-4">
                            <Search class="size-12 opacity-20" />
                            <p class="text-sm font-medium italic">Seleccione un cliente para ver sus facturas</p>
                        </div>

                        <div v-else-if="pendingReceivables.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-muted-foreground gap-4">
                            <CheckCircle2 class="size-12 text-green-500/20" />
                            <p class="text-sm font-medium">El cliente no tiene deudas pendientes</p>
                        </div>

                        <table v-else class="w-full text-sm text-left">
                            <thead class="border-b bg-muted/50 text-[10px] font-bold uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-5">Factura</th>
                                    <th class="px-6 py-5">Saldo Pendiente</th>
                                    <th class="px-6 py-5 text-center w-48">Monto a Cobrar</th>
                                    <th class="px-6 py-5 text-right w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="invoice in form.payments" :key="invoice.acr_id" class="group hover:bg-muted/30 transition-colors">
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold">#{{ invoice.invoice_number }}</span>
                                            <span class="text-[10px] uppercase tracking-widest font-bold text-muted-foreground">Factura de Venta</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1 font-mono font-bold text-muted-foreground">
                                                <span class="text-[10px] text-muted-foreground/60">{{ invoice.currency }}</span>
                                                {{ Number(invoice.balance).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                            </div>
                                            <div
                                                v-if="invoice.currency !== form.payment_currency"
                                                class="flex items-center gap-1 font-mono text-[11px] text-primary font-bold"
                                            >
                                                <span class="text-[10px] text-primary/60">≈ {{ form.payment_currency }}</span>
                                                {{ calculateConvertedBalance(invoice).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="relative">
                                            <Input
                                                type="number"
                                                step="0.0001"
                                                :model-value="invoice.amount"
                                                @update:model-value="invoice.amount = Math.min(Number($event), calculateConvertedBalance(invoice))"
                                                class="text-right font-bold tabular-nums transition-all group-hover:border-primary/50"
                                            />
                                            <button
                                                @click="invoice.amount = calculateConvertedBalance(invoice)"
                                                class="absolute left-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-primary hover:text-primary/80 opacity-0 group-hover:opacity-100 transition-opacity"
                                            >
                                                MÁX
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div v-if="invoice.amount > 0" class="flex justify-center text-green-500">
                                            <CheckCircle2 class="size-4" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <Building2 class="size-4 text-primary" />
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Origen de Fondos</span>
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="flex flex-col gap-5">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Banco y Cuenta</label>
                                    <Select v-model="form.bank_account_id">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccione cuenta bancaria" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="ba in filteredBankAccounts" :key="ba.id" :value="ba.id.toString()">
                                                <div class="flex flex-col items-start py-0.5">
                                                    <span class="font-bold">{{ ba.bank_name }}</span>
                                                    <span class="text-[10px] font-mono text-muted-foreground">{{ ba.bank_account_number }} ({{ ba.bank_currency }})</span>
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.bank_account_id" class="mt-1 text-xs text-destructive">{{ form.errors.bank_account_id }}</span>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Tipo de Transacción</label>
                                    <Select v-model="form.payment_method" :disabled="isEditMode">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Método de cobro" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="m in methods" :key="m" :value="m">{{ m }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <span v-if="form.errors.payment_method" class="mt-1 text-xs text-destructive">{{ form.errors.payment_method }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col gap-5">
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Referencia / Voucher</label>
                                    <Input v-model="form.payment_reference" placeholder="N° de transacción o referencia" :class="{'border-destructive': form.errors.payment_reference}" />
                                    <span v-if="form.errors.payment_reference" class="mt-1 text-xs text-destructive">{{ form.errors.payment_reference }}</span>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Observaciones</label>
                                    <Input v-model="form.payment_description" placeholder="Opcional" :class="{'border-destructive': form.errors.payment_description}" />
                                    <span v-if="form.errors.payment_description" class="mt-1 text-xs text-destructive">{{ form.errors.payment_description }}</span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selectedBankAccount"
                            class="flex items-center gap-4 rounded-xl border p-4"
                            :class="selectedBankAccount.bank_balance >= totalInPaymentCurrency ? 'border-green-500/10 bg-green-500/5' : 'border-red-500/10 bg-red-500/5'"
                        >
                            <div
                                :class="selectedBankAccount.bank_balance >= totalInPaymentCurrency ? 'bg-green-500/20 text-green-500' : 'bg-red-500/20 text-red-500'"
                                class="rounded-lg p-2.5"
                            >
                                <Landmark class="size-5" />
                            </div>
                            <div class="flex-1">
                                <p class="text-[10px] font-bold uppercase text-muted-foreground">Saldo Disponible en Banco</p>
                                <p class="text-sm font-bold">{{ selectedBankAccount.formatted_balance }}</p>
                            </div>
                            <div v-if="selectedBankAccount.bank_balance < totalInPaymentCurrency" class="flex items-center gap-1.5 rounded-full bg-red-500/10 px-3 py-1 text-[10px] font-bold uppercase text-red-500">
                                <AlertCircle class="size-3" /> Fondos Insuficientes
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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
