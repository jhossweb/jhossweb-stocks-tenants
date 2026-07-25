<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Landmark, Calculator } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormRowDelete from '@/components/Form/FormRowDelete.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
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
}>();

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useTransactionForm(prodRef, wareRef, ratesRef, latestRef);

const valorcam = ref(
    props.latest_rate ? Number(props.latest_rate.rate_usd) : 1,
);

const form = useForm({
    sls_number: '',
    sls_date: new Date().toISOString().split('T')[0],
    sls_customer_id: '',
    sls_valorcam: valorcam.value,
    sls_coin: 'USD',
    sls_payment_condition: 'cash',
    sls_credit_days: 0,
    sls_due_date: new Date().toISOString().split('T')[0],
    items: [] as any[],
    sls_subtotal: 0,
    sls_iva: 0,
    sls_exento: 0,
    sls_total: 0,
});

form.sls_valorcam = valorcam.value;

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
        const lineTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
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
    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
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
        item.mvs_unit_price = form.sls_coin === 'USD' ? usdPrice : usdPrice * tasaManual;
    }

    recalc();
};

const handleExchangeUpdate = () => {
    tx.updateExchangeRate(form.sls_date, form.sls_coin, valorcam, updatePriceFromRef);
};

const handleQuantityInput = () => {
    recalc();
};

const getItemTotal = (item: any) => {
    const baseTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
    const ivaRate = getProductIvaRate(item.mvs_pro_code);

    return (baseTotal + (baseTotal * ivaRate)).toFixed(2);
};

const submit = () => {
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
            @save="submit"
        />

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li
                    v-for="(error, key) in form.errors"
                    :key="key"
                >
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
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">N° Factura</label>
                            <Input
                                v-model="form.sls_number"
                                type="text"
                            />
                            <p
                                v-if="form.errors.sls_number"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_number }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Cliente</label>
                            <SelectorModal
                                title="Seleccionar Cliente"
                                description="Busque y seleccione un cliente de la lista"
                                :items="customers"
                                :search-fields="['clt_name', 'clt_email', 'clt_phone']"
                                :display-fields="[
                                    { label: 'Nombre', key: 'clt_name' },
                                    { label: 'Email', key: 'clt_email' },
                                ]"
                                :trigger-label="tx.getCustomerName(customers, form.sls_customer_id)"
                                :selected-value="form.sls_customer_id"
                                @select="selectCustomer"
                            />
                            <p
                                v-if="form.errors.sls_customer_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_customer_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                                <Input
                                    v-model="form.sls_date"
                                    type="date"
                                    @change="handleDateChange"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Moneda</label>
                                <select
                                    v-model="form.sls_coin"
                                    class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Condición de Pago</label>
                                <select
                                    v-model="form.sls_payment_condition"
                                    class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Días Crédito</label>
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
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha Vencimiento</label>
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
                            <label class="mb-1.5 block text-xs font-bold uppercase text-muted-foreground">Tasa de Cambio</label>
                            <div class="mb-2 text-xs text-muted-foreground">
                                Tasa del día: <span class="font-semibold text-primary">{{ valorcam }}</span>
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

                <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg">
                    <Calculator class="text-primary-foreground/10 absolute -bottom-4 -right-4 size-24" />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>SUBTOTAL</span>
                            <span>{{ form.sls_subtotal }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>EXENTO</span>
                            <span>{{ form.sls_exento }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>IVA</span>
                            <span>{{ form.sls_iva }}</span>
                        </div>
                        <div class="flex items-end justify-between border-t border-primary-foreground/10 pt-3">
                            <span class="text-sm font-bold uppercase">Total {{ form.sls_coin }}</span>
                            <span class="text-3xl font-black tabular-nums">{{ form.sls_total }}</span>
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
                            class="hover:bg-muted/30 transition-colors"
                        >
                            <td class="min-w-[220px] p-3">
                                <SelectorModal
                                    title="Seleccionar Producto"
                                    :items="products"
                                    :search-fields="['pro_name', 'pro_code']"
                                    :display-fields="[
                                        { label: 'Código', key: 'pro_code', class: 'font-mono text-xs' },
                                        { label: 'Nombre', key: 'pro_name' },
                                        { label: 'Und', key: 'pro_und', class: 'font-mono text-xs text-center' },
                                        { label: 'Precio $', key: 'latest_price', class: 'font-mono text-xs text-right' },
                                        { label: 'Stock', key: 'total_stock', class: 'font-mono text-xs text-right' },
                                    ]"
                                    :trigger-label="tx.getProductName(item.mvs_pro_code)"
                                    :selected-value="item.mvs_pro_code"
                                    trigger-class="bg-transparent border-none hover:bg-muted/50 w-full"
                                    @select="(p: any) => selectProduct(index, p)"
                                />
                            </td>
                            <td class="p-3">
                                <select
                                    v-model="item.mvs_war_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                >
                                    <option
                                        value=""
                                    >
                                        Seleccione...
                                    </option>
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
                                    class="bg-muted inline-block size-5 animate-pulse rounded"
                                />
                                <span
                                    v-else
                                    class="inline-block rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
                                >
                                    {{ tx.getProductIvaLabel(item.mvs_pro_code) }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <input
                                        v-model="item.mvs_quantity"
                                        type="number"
                                        :step="tx.getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                        :min="tx.getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                        class="border-input bg-background w-20 rounded-md border py-1.5 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                        @input="handleQuantityInput"
                                    >
                                    <span
                                        v-if="item.mvs_pro_code"
                                        class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-primary"
                                    >
                                        {{ tx.getUndLabel(tx.getProductUnd(item.mvs_pro_code)) }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-3">
                                <span class="block w-full text-right font-bold py-1.5">
                                    {{ Number(item.mvs_unit_price).toFixed(4) }}
                                </span>
                            </td>
                            <td class="p-3 text-right">
                                <span
                                    v-if="item._pending"
                                    class="bg-muted inline-block h-4 w-16 animate-pulse rounded"
                                />
                                <span
                                    v-else
                                    class="font-mono text-xs font-medium text-primary"
                                >
                                    {{ item.mvs_unit_price_ref }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-bold text-muted-foreground">
                                <span
                                    v-if="item._pending"
                                    class="bg-muted inline-block h-4 w-16 animate-pulse rounded"
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
