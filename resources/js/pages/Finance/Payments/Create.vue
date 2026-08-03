<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Loader2,
    Search,
    CheckCircle2,
    CreditCard,
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import BasePaymentPage from '@/components/payments/BasePaymentPage.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Badge as UIBadge } from '@/components/ui/badge';
import Input from '@/components/ui/input/Input.vue';
import { Label } from '@/components/ui/label';

import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import paymentRoutes from '@/routes/payments';

const props = defineProps<{
    payment?: any;
    suppliers: any[];
    bankAccounts: any[];
    latestRate: any;
    rates: any[];
    currencies: string[];
    methods: string[];
}>();

const isEditMode = computed(() => !!props.payment);

const { convertCurrency } = useCurrencyConversion();

const pendingInvoices = ref<any[]>([]);
const isLoadingInvoices = ref(false);

const form = useForm({
    payment_date: props.payment?.bank_transaction?.created_at ? props.payment.bank_transaction.created_at.split('T')[0] : new Date().toISOString().split('T')[0],
    supplier_id: props.payment?.account_payable?.purchase_order?.invoice_supplier_id?.toString() || props.payment?.account_payable?.purchase_order?.supplier?.id?.toString() || '',
    bank_account_id: props.payment?.bank_transaction?.btr_bank_account_id?.toString() || '',
    payment_currency: props.payment?.bank_transaction?.btr_coin || 'USD',
    payment_rate: props.payment?.bank_transaction?.btr_valorcam ? Number(props.payment.bank_transaction.btr_valorcam) : (props.latestRate ? Number(props.latestRate.rate_usd) : 1),
    payment_method: props.payment?.bank_transaction?.btr_type || 'TRANSFERENCIA',
    payment_reference: props.payment?.bank_transaction?.btr_reference || '',
    payment_description: props.payment?.bank_transaction?.btr_description || '',
    amount: 0,
    payments: [] as { acp_id: number, invoice_number: string, balance: number, currency: string, amount: number }[],
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

const fetchPendingInvoices = async (supplierId: string | number) => {
    if (!supplierId) {
        pendingInvoices.value = [];
        form.payments = [];

        return;
    }

    isLoadingInvoices.value = true;

    try {
        const response = await fetch(`/api/suppliers/${supplierId}/pending-invoices`);

        if (!response.ok) {
throw new Error('Error al obtener facturas');
}
        
        const data = await response.json();
        pendingInvoices.value = data;
        
        form.payments = pendingInvoices.value.map(inv => ({
            acp_id: inv.id,
            invoice_number: inv.acp_purchase_number,
            balance: Number(inv.acp_balance),
            currency: inv.purchase_order?.invoice_coin || 'USD',
            amount: 0
        }));
    } catch (error) {
        console.error('Error fetching invoices:', error);
    } finally {
        isLoadingInvoices.value = false;
    }
};

const selectSupplier = (supplier: any) => {
    form.supplier_id = supplier.id;
    fetchPendingInvoices(supplier.id);
};

const getSupplierName = (id: string | number) => {
    const s = props.suppliers.find(s => s.id == id);

    return s ? s.supplier_name : 'Seleccione proveedor';
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
        })).put(`/payments/${props.payment.id}`);
        
        return;
    }

    if (totalToPay.value <= 0) {
        alert('Debe ingresar al menos un monto a pagar');

        return;
    }
    
    form.post(paymentRoutes.store.url(), {
        onSuccess: () => {
            // Success logic
        }
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

const updateExchangeRate = () => {
    const selectedRate = getBestRateForDate(form.payment_date);

    if (!selectedRate) {
        return;
    }

    if (form.payment_currency === 'USD' || form.payment_currency === 'BS' || form.payment_currency === 'VES') {
        form.payment_rate = Number(selectedRate.rate_usd);
    } else if (form.payment_currency === 'COP') {
        form.payment_rate = Number(selectedRate.rate_cop);
    } else if (form.payment_currency === 'EUR') {
        form.payment_rate = Number(selectedRate.rate_eur);
    }
};

watch(() => [form.payment_currency, form.payment_date], () => {
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
        const docCurrency = props.payment.account_payable?.purchase_order?.invoice_coin || 'USD';
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

        const acp = props.payment.account_payable;
        const currentBalance = Number(acp?.acp_balance || 0);
        const originallyPaid = Number(props.payment.apy_amount || 0);
        
        form.payments = [{
            acp_id: acp?.id,
            invoice_number: acp?.acp_purchase_number,
            balance: currentBalance + originallyPaid,
            currency: docCurrency,
            amount: originallyPaid
        }];
    } else {
        updateExchangeRate();
    }
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
    <Head :title="isEditMode ? 'Editar Pago' : 'Comprobante de Pago'" />

    <BasePaymentPage
        :title="isEditMode ? 'Editar Pago' : 'Comprobante de Pago'"
        :subtitle="isEditMode ? 'Actualización de pago registrado' : 'Registro de egresos y abonos a proveedores'"
        back-route="/accounts-payable"
        :form="form"
        :errors="form.errors"
        :processing="form.processing"
        :submit-label="isEditMode ? 'Actualizar Pago' : 'Generar Pago'"
        :bank-accounts="bankAccounts"
        :latest-rate="latestRate"
        :currencies="currencies"
        :methods="methods"
        total-label="Total a Liquidar"
        :total-amount="totalInPaymentCurrency"
        :total-currency="form.payment_currency"
        :original-total="originalTotal"
        :original-currency="originalCurrency"
        :estimated-diferencial="estimatedDiferencial"
        @submit="submit"
    >
        <template #entity-selector>
            <div class="space-y-2">
                <Label class="text-zinc-400 text-xs font-semibold">Proveedor</Label>
                <div v-if="isEditMode" class="h-11 bg-zinc-950/50 border border-zinc-800 rounded-xl px-3 flex items-center text-sm text-zinc-300">
                    {{ getSupplierName(form.supplier_id) }}
                </div>
                <SelectorModal v-else
                    title="Seleccionar Proveedor"
                    description="Busque y seleccione el proveedor a pagar"
                    :items="suppliers"
                    :searchFields="['supplier_name', 'id']"
                    :displayFields="[
                        { label: 'Nombre', key: 'supplier_name' },
                        { label: 'ID', key: 'id' }
                    ]"
                    :triggerLabel="getSupplierName(form.supplier_id)"
                    :selectedValue="form.supplier_id"
                    @select="selectSupplier"
                />
                <span v-if="form.errors.supplier_id" class="text-xs text-red-500 font-bold block mt-1">{{ form.errors.supplier_id }}</span>
            </div>
        </template>

        <template #items-table>
            <div class="bg-zinc-900 rounded-2xl border border-zinc-800 shadow-sm overflow-hidden flex flex-col min-h-[400px]">
                <div class="p-5 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-500/10 rounded-lg text-indigo-400">
                            <CreditCard class="w-5 h-5" />
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400">Facturas Pendientes</h3>
                    </div>
                    <UIBadge v-if="pendingInvoices.length" variant="outline" class="bg-zinc-800 border-zinc-700 text-zinc-400 text-[10px]">
                        {{ pendingInvoices.length }} facturas encontradas
                    </UIBadge>
                </div>

                <div class="flex-1 overflow-x-auto relative">
                    <div v-if="isLoadingInvoices" class="absolute inset-0 bg-zinc-950/20 backdrop-blur-sm flex items-center justify-center z-10">
                        <Loader2 class="w-8 h-8 animate-spin text-indigo-500" />
                    </div>

                    <div v-if="!form.supplier_id" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                        <Search class="w-12 h-12 opacity-20" />
                        <p class="text-sm font-medium italic">Seleccione un proveedor para ver sus facturas</p>
                    </div>

                    <div v-else-if="pendingInvoices.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 gap-4">
                        <CheckCircle2 class="w-12 h-12 text-green-500/20" />
                        <p class="text-sm font-medium">El proveedor no tiene deudas pendientes</p>
                    </div>

                    <table v-else class="w-full text-sm text-left">
                        <thead class="bg-zinc-950/50 text-zinc-500 text-[10px] uppercase font-black border-b border-zinc-800">
                            <tr>
                                <th class="px-6 py-5">Factura</th>
                                <th class="px-6 py-5">Saldo Pendiente</th>
                                <th class="px-6 py-5 text-center w-48">Monto a Pagar</th>
                                <th class="px-6 py-5 text-right w-12 text-zinc-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="invoice in form.payments" :key="invoice.acp_id" class="group hover:bg-zinc-800/30 transition-colors">
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="font-black text-zinc-200">#{{ invoice.invoice_number }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-1 font-mono text-zinc-400 font-bold">
                                            <span class="text-[10px] text-zinc-600">{{ invoice.currency }}</span>
                                            {{ Number(invoice.balance).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                        </div>
                                        <div
                                            v-if="invoice.currency !== form.payment_currency"
                                            class="flex items-center gap-1 font-mono text-[11px] text-amber-400 font-bold"
                                        >
                                            <span class="text-[10px] text-amber-600">≈ {{ form.payment_currency }}</span>
                                            {{ calculateConvertedBalance(invoice).toLocaleString('en-US', { minimumFractionDigits: 4 }) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <div class="relative">
                                        <Input
                                            type="number"
                                            step="0.0001"
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
