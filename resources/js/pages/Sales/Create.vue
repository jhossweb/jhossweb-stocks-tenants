<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Landmark,
    Calculator,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormRowDelete from '@/components/Form/FormRowDelete.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
import PaymentSection from '@/components/payments/PaymentSection.vue';
import CustomerCreateModal from '@/components/sales/CustomerCreateModal.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { useTransactionForm } from '@/composables/Transaction/useTransactionForm';
import type { ColumnConfig } from '@/types/Transaction';

const props = defineProps<{
    customers: any[];
    products: any[];
    warehouses: any[];
    rates: any[];
    coins: string[];
    latest_rate: any;
    transactionTypes: any[];
    ivas: string[];
    bankAccounts: any[];
    methods: string[];
    sale_preset?: any;
}>();

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useTransactionForm(prodRef, wareRef, ratesRef, latestRef);

const valorcam = ref(
    props.sale_preset?.sls_valorcam
        ? Number(props.sale_preset.sls_valorcam)
        : props.latest_rate
          ? Number(props.latest_rate.rate_usd)
          : 1,
);

const presetItems = (props.sale_preset?.items ?? []).map((item: any) => ({
    mvs_pro_code: item.mvs_pro_code || '',
    mvs_war_id: item.mvs_war_id ?? '',
    mvs_quantity: Number(item.mvs_quantity) || 1,
    mvs_unit_price: Number(item.mvs_unit_price) || 0,
    mvs_unit_price_ref: Number(item.mvs_unit_price_ref) || 0,
    mvs_pro_iva: Number(item.mvs_pro_iva) || 0,
    _pending: false,
}));

const form = useForm({
    sls_number: props.sale_preset?.sls_number ?? '',
    sls_date:
        props.sale_preset?.sls_date ?? new Date().toISOString().split('T')[0],
    sls_customer_id: props.sale_preset?.sls_customer_id
        ? String(props.sale_preset.sls_customer_id)
        : '',
    sls_valorcam: valorcam.value,
    sls_coin: props.sale_preset?.sls_coin ?? 'USD',
    sls_payment_condition: props.sale_preset?.sls_payment_condition ?? 'cash',
    sls_credit_days: props.sale_preset?.sls_credit_days ?? 0,
    sls_due_date:
        props.sale_preset?.sls_due_date ??
        new Date().toISOString().split('T')[0],
    sls_reference: props.sale_preset?.sls_reference ?? '',
    items: presetItems as any[],
    sls_subtotal: props.sale_preset?.sls_subtotal ?? 0,
    sls_iva: props.sale_preset?.sls_iva ?? 0,
    sls_exento: props.sale_preset?.sls_exento ?? 0,
    sls_total: props.sale_preset?.sls_total ?? 0,
    payment: {
        payment_date: new Date().toISOString().split('T')[0],
        vueltos: [] as {
            vuelto_amount: number | string;
            vuelto_currency: string;
            vuelto_bank_account_id: string;
        }[],
        payments: [] as {
            payment_currency: string;
            payment_rate: string;
            bank_account_id: string;
            payment_method: string;
            payment_reference: string;
            payment_description: string;
            payment_amount: number;
        }[],
    },
});

form.sls_valorcam = valorcam.value;

const step = ref<'ticket' | 'payment'>('ticket');
const clientError = ref('');

const isCash = computed(() => form.sls_payment_condition === 'cash');

const actionLabel = computed(() => {
    if (!isCash.value) {
        return 'Guardar';
    }

    return step.value === 'ticket' ? 'Pagar' : 'Confirmar';
});

const actionLoadingLabel = computed(() => {
    if (form.processing) {
        return 'Procesando...';
    }

    return !isCash.value || step.value === 'ticket'
        ? 'Guardando...'
        : 'Confirmando...';
});

const createBlankItem = () => ({
    mvs_pro_code: '',
    mvs_war_id: '',
    mvs_quantity: 1,
    mvs_unit_price_ref: 0,
    mvs_unit_price: 0,
    mvs_pro_iva: 0,
    _pending: false,
});

if (form.items.length === 0) {
    form.items.push(createBlankItem());
}

const getProductIvaRate = (proCode: string) => {
    return proCode ? tx.getProductIvaRate(proCode) : 0;
};

const recalc = () => {
    let subtotal = 0;
    let sumIva = 0;
    let exento = 0;

    for (const item of form.items) {
        const lineTotal =
            Number(item.mvs_quantity) * Number(item.mvs_unit_price);
        subtotal += lineTotal;
        const ivaRate = getProductIvaRate(item.mvs_pro_code);
        const ivaAmount = lineTotal * ivaRate;
        sumIva += ivaAmount;
        item.mvs_pro_iva = ivaRate * 100;

        if (ivaRate === 0) {
            exento += lineTotal;
        }
    }

    form.sls_subtotal = Number(subtotal.toFixed(4));
    form.sls_iva = Number(sumIva.toFixed(4));
    form.sls_exento = Number(exento.toFixed(4));
    form.sls_total = Number((subtotal + sumIva).toFixed(4));
};

const updateDueDate = () => {
    if (!form.sls_date) {
        return;
    }

    const parts = form.sls_date.split('-');
    const date = new Date(
        Number(parts[0]),
        Number(parts[1]) - 1,
        Number(parts[2]),
    );
    date.setDate(date.getDate() + Number(form.sls_credit_days));
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    form.sls_due_date = `${y}-${m}-${d}`;
};

const handleConditionChange = () => {
    if (form.sls_payment_condition === 'cash') {
        form.sls_credit_days = 0;
    }

    updateDueDate();
};

const handleDateChange = () => {
    handleExchangeUpdate();
    updateDueDate();
};

const selectCustomer = (customer: any) => {
    form.sls_customer_id = customer.id;

    if (customer.clt_credit_days > 0) {
        form.sls_payment_condition = 'credit';
        form.sls_credit_days = customer.clt_credit_days;
        updateDueDate();
    }
};

const customerModalRef = ref<InstanceType<
    typeof CustomerCreateModal
> | null>(null);

const openCustomerModal = () => {
    clientError.value = '';
    customerModalRef.value?.open();
};

const onCustomerCreated = (customer: { id: number }) => {
    selectCustomer(customer as any);
};

const selectProduct = (index: number, product: any) => {
    const item = form.items[index];
    item.mvs_pro_code = product.pro_code;
    item._pending = true;

    const usdPrice = Number(product.latest_price) || 0;
    const tasaManual = Number(form.sls_valorcam) || 1;
    item.mvs_unit_price_ref = usdPrice;

    if (form.sls_coin === 'USD') {
        item.mvs_unit_price = usdPrice;
    } else {
        item.mvs_unit_price = usdPrice * tasaManual;
    }

    setTimeout(() => {
        item._pending = false;
        recalc();
    }, 150);
};

const addItem = () => {
    tx.addItem(form.items, createBlankItem);
};

const removeItem = (index: number) => {
    tx.removeItem(form.items, index, recalc);
};

const updatePriceFromRef = () => {
    const tasaManual = Number(form.sls_valorcam) || 1;

    for (const item of form.items) {
        const usdPrice = Number(item.mvs_unit_price_ref) || 0;
        item.mvs_unit_price =
            form.sls_coin === 'USD' ? usdPrice : usdPrice * tasaManual;
    }

    recalc();
};

const handleExchangeUpdate = () => {
    tx.updateExchangeRate(
        form.sls_date,
        form.sls_coin,
        valorcam,
        updatePriceFromRef,
    );
};

const handleQuantityInput = () => {
    recalc();
};

const getItemTotal = (item: any) => {
    const baseTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
    const ivaRate = getProductIvaRate(item.mvs_pro_code);

    return (baseTotal + baseTotal * ivaRate).toFixed(4);
};

const validateTicket = (): string => {
    if (!form.sls_number.trim()) {
        return 'Ingrese el número de factura.';
    }

    if (!form.sls_customer_id) {
        return 'Seleccione un cliente.';
    }

    if (form.items.length === 0) {
        return 'Agregue al menos un producto.';
    }

    for (const item of form.items) {
        if (!item.mvs_pro_code) {
            return 'Todos los productos deben estar seleccionados.';
        }

        if (!item.mvs_war_id) {
            return 'Todos los productos deben tener almacén asignado.';
        }

        if (Number(item.mvs_quantity) <= 0) {
            return 'Las cantidades deben ser mayores a cero.';
        }
    }

    return '';
};

const goToPayment = () => {
    clientError.value = validateTicket();

    if (clientError.value) {
        return;
    }

    form.payment.payment_date = new Date().toISOString().split('T')[0];
    form.payment.payments = [
        {
            payment_currency: form.sls_coin,
            payment_rate: '1',
            bank_account_id: '',
            payment_method: props.methods[0] || '',
            payment_reference: '',
            payment_description: '',
            payment_amount: Number(form.sls_total.toFixed(4)),
        },
    ];
    step.value = 'payment';

    setTimeout(() => {
        const section = document.getElementById('payment-section');

        if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 50);
};

const submit = () => {
    if (isCash.value && step.value === 'ticket') {
        goToPayment();

        return;
    }

    form.sls_valorcam = valorcam.value;
    form.post('/sales', {
        preserveScroll: true,
    });
};

onMounted(() => {
    handleExchangeUpdate();
    updateDueDate();
});

const columns: ColumnConfig[] = [
    { key: 'product', label: 'Producto' },
    { key: 'warehouse', label: 'Almacén' },
    { key: 'iva', label: 'IVA', align: 'center', class: 'text-primary' },
    { key: 'qty', label: 'Cant.', align: 'center', width: '6rem' },
    { key: 'price', label: 'Precio Unit.', align: 'right' },
    { key: 'ref', label: 'Ref ($)', align: 'right', class: 'text-primary' },
    { key: 'total', label: 'Total', align: 'right' },
];
</script>

<template>
    <Head title="Nueva Venta" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Nueva Venta"
            subtitle="Registro de facturas de venta e inventario"
            back-url="/sales"
            :processing="form.processing"
            :label="actionLabel"
            :loading-label="actionLoadingLabel"
            @save="submit"
        />

        <div
            v-if="clientError"
            class="flex items-start gap-3 rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <AlertCircle class="mt-0.5 size-4 shrink-0 text-destructive" />
            <p class="text-sm font-semibold text-destructive">
                {{ clientError }}
            </p>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in form.errors" :key="key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <FormSection>
                    <template #header>
                        <Landmark class="size-4 text-primary" />
                        <span>Configuración General</span>
                    </template>

                    <div class="flex flex-col gap-5">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >N° Factura</label
                            >
                            <Input v-model="form.sls_number" type="text" />
                            <p
                                v-if="form.errors.sls_number"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_number }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Cliente</label
                            >
                            <SelectorModal
                                title="Seleccionar Cliente"
                                description="Busque y seleccione un cliente de la lista"
                                :items="customers"
                                :search-fields="[
                                    'clt_name',
                                    'clt_email',
                                    'clt_phone',
                                ]"
                                :display-fields="[
                                    { label: 'Nombre', key: 'clt_name' },
                                    { label: 'Email', key: 'clt_email' },
                                ]"
                                :trigger-label="
                                    tx.getCustomerName(
                                        customers,
                                        form.sls_customer_id,
                                    )
                                "
                                :selected-value="form.sls_customer_id"
                                @select="selectCustomer"
                            >
                                <template #footer>
                                    <button
                                        type="button"
                                        @click="openCustomerModal"
                                        class="w-full flex items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-800 bg-zinc-950/50 px-3 py-2.5 text-sm font-semibold text-zinc-400 hover:text-indigo-400 hover:bg-indigo-500/10 hover:border-indigo-500/40 transition-all"
                                    >
                                        + Nuevo Cliente
                                    </button>
                                </template>
                            </SelectorModal>
                            <p
                                v-if="form.errors.sls_customer_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_customer_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Fecha</label
                                >
                                <Input
                                    v-model="form.sls_date"
                                    type="date"
                                    @change="handleDateChange"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Moneda</label
                                >
                                <select
                                    v-model="form.sls_coin"
                                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                    @change="handleExchangeUpdate"
                                >
                                    <option
                                        v-for="c in coins"
                                        :key="c"
                                        :value="c"
                                    >
                                        {{ c }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Condición de Pago</label
                                >
                                <select
                                    v-model="form.sls_payment_condition"
                                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                    @change="handleConditionChange"
                                >
                                    <option value="cash">Contado</option>
                                    <option value="credit">Crédito</option>
                                </select>
                                <p
                                    v-if="form.errors.sls_payment_condition"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ form.errors.sls_payment_condition }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Días Crédito</label
                                >
                                <Input
                                    v-model="form.sls_credit_days"
                                    type="number"
                                    min="0"
                                    max="365"
                                    @input="updateDueDate"
                                />
                                <p
                                    v-if="form.errors.sls_credit_days"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ form.errors.sls_credit_days }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Fecha Vencimiento</label
                            >
                            <Input
                                v-model="form.sls_due_date"
                                type="date"
                                disabled
                            />
                            <p
                                v-if="form.errors.sls_due_date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_due_date }}
                            </p>
                        </div>

                        <div class="rounded-lg border bg-muted/30 p-4">
                            <label
                                class="mb-1.5 block text-xs font-bold text-muted-foreground uppercase"
                                >Tasa de Cambio</label
                            >
                            <div class="mb-2 text-xs text-muted-foreground">
                                Tasa del día:
                                <span class="font-semibold text-primary">{{
                                    valorcam
                                }}</span>
                            </div>
                            <Input
                                v-model="valorcam"
                                type="number"
                                step="0.0001"
                                class="font-mono text-sm font-bold"
                                @input="updatePriceFromRef"
                            />
                        </div>
                    </div>
                </FormSection>

                <div
                    class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg"
                >
                    <Calculator
                        class="absolute -right-4 -bottom-4 size-24 text-primary-foreground/10"
                    />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>SUBTOTAL</span>
                            <span>{{ form.sls_subtotal }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>EXENTO</span>
                            <span>{{ form.sls_exento }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>IVA</span>
                            <span>{{ form.sls_iva }}</span>
                        </div>
                        <div
                            class="flex items-end justify-between border-t border-primary-foreground/10 pt-3"
                        >
                            <span class="text-sm font-bold uppercase"
                                >Total {{ form.sls_coin }}</span
                            >
                            <span class="text-3xl font-black tabular-nums">{{
                                form.sls_total
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <ItemsTable
                    :columns="columns"
                    :empty="form.items.length === 0"
                    @add="addItem"
                >
                    <TransitionGroup name="row">
                        <tr
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="min-w-[220px] p-3">
                                <SelectorModal
                                    title="Seleccionar Producto"
                                    :items="products"
                                    :search-fields="['pro_name', 'pro_code']"
                                    :display-fields="[
                                        {
                                            label: 'Código',
                                            key: 'pro_code',
                                            class: 'font-mono text-xs',
                                        },
                                        { label: 'Nombre', key: 'pro_name' },
                                        {
                                            label: 'Und',
                                            key: 'pro_und',
                                            class: 'font-mono text-xs text-center',
                                        },
                                        {
                                            label: 'Precio $',
                                            key: 'latest_price',
                                            class: 'font-mono text-xs text-right',
                                        },
                                        {
                                            label: 'Stock',
                                            key: 'total_stock',
                                            class: 'font-mono text-xs text-right',
                                        },
                                    ]"
                                    :trigger-label="
                                        tx.getProductName(item.mvs_pro_code)
                                    "
                                    :selected-value="item.mvs_pro_code"
                                    trigger-class="bg-transparent border-none hover:bg-muted/50 w-full"
                                    @select="
                                        (p: any) => selectProduct(index, p)
                                    "
                                />
                            </td>
                            <td class="p-3">
                                <select
                                    v-model="item.mvs_war_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none"
                                >
                                    <option value="">Seleccione...</option>
                                    <option
                                        v-for="w in warehouses"
                                        :key="w.id"
                                        :value="w.id"
                                    >
                                        {{ w.war_name || w.id }}
                                    </option>
                                </select>
                            </td>
                            <td class="p-3 text-center">
                                <span
                                    v-if="item._pending"
                                    class="inline-block size-5 animate-pulse rounded bg-muted"
                                />
                                <span
                                    v-else
                                    class="inline-block rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
                                >
                                    {{
                                        tx.getProductIvaLabel(item.mvs_pro_code)
                                    }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <input
                                        v-model="item.mvs_quantity"
                                        type="number"
                                        :step="
                                            tx.getProductUnd(
                                                item.mvs_pro_code,
                                            ) === 'und'
                                                ? 1
                                                : 0.01
                                        "
                                        :min="
                                            tx.getProductUnd(
                                                item.mvs_pro_code,
                                            ) === 'und'
                                                ? 1
                                                : 0.01
                                        "
                                        class="w-20 rounded-md border border-input bg-background py-1.5 text-center text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                                        @input="handleQuantityInput"
                                    />
                                    <span
                                        v-if="item.mvs_pro_code"
                                        class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary uppercase"
                                    >
                                        {{
                                            tx.getUndLabel(
                                                tx.getProductUnd(
                                                    item.mvs_pro_code,
                                                ),
                                            )
                                        }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3">
                                <span
                                    class="block w-full py-1.5 text-right font-bold"
                                >
                                    {{ Number(item.mvs_unit_price).toFixed(4) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <span
                                    v-if="item._pending"
                                    class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
                                />
                                <span
                                    v-else
                                    class="font-mono text-xs font-medium text-primary"
                                >
                                    {{ item.mvs_unit_price_ref }}
                                </span>
                            </td>
                            <td
                                class="p-3 text-right font-bold text-muted-foreground"
                            >
                                <span
                                    v-if="item._pending"
                                    class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
                                />
                                <span v-else>{{ getItemTotal(item) }}</span>
                            </td>
                            <td class="p-3 text-center">
                                <FormRowDelete @remove="removeItem(index)" />
                            </td>
                        </tr>
                    </TransitionGroup>
                </ItemsTable>
            </div>
        </div>

        <FormSection
            v-if="isCash && step === 'payment'"
            id="payment-section"
            class="scroll-mt-24"
        >
            <template #header>
                <Landmark class="size-4 text-primary" />
                <span>Pago de la Factura</span>
            </template>

            <PaymentSection
                v-model="form.payment"
                :bank-accounts="props.bankAccounts"
                :latest-rate="props.latest_rate"
                :rates="props.rates"
                :methods="props.methods"
                :currencies="props.coins"
                :errors="form.errors"
                :doc-currency="form.sls_coin"
                :doc-total="Number(form.sls_total) || 0"
                allow-excess
            />

            <template #actions>
                <div class="flex flex-col gap-3">
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50"
                        @click="submit"
                    >
                        <CheckCircle2 v-if="!form.processing" class="size-4" />
                        <span>
                            {{
                                form.processing
                                    ? 'Procesando...'
                                    : 'Confirmar Venta y Pago'
                            }}
                        </span>
                    </button>
                    <button
                        type="button"
                        class="text-sm font-medium text-muted-foreground hover:text-foreground"
                        @click="step = 'ticket'"
                    >
                        ← Volver al ticket
                    </button>
                </div>
            </template>
        </FormSection>

        <CustomerCreateModal
            ref="customerModalRef"
            @created="onCustomerCreated"
        />
    </div>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type='number'] {
    -moz-appearance: textfield;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
.row-enter-active,
.row-leave-active {
    transition: all 0.2s ease;
}
.row-enter-from {
    opacity: 0;
    transform: translateY(-8px);
}
.row-leave-to {
    opacity: 0;
    transform: translateX(8px);
}
.row-move {
    transition: transform 0.2s ease;
}
</style>
