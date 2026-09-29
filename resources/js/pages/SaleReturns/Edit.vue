<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Landmark, Calculator, Loader2 } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { useTransactionForm } from '@/composables/Transaction/useTransactionForm';
import { formatDate, today } from '@/composables/useDate';
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
    saleReturn: any;
}>();

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useTransactionForm(prodRef, wareRef, ratesRef, latestRef);

const slsDueDate = props.saleReturn.sls_due_date
    ? String(props.saleReturn.sls_due_date).split(' ')[0]
    : today();

const form = useForm({
    sls_number: props.saleReturn.sls_number || '',
    sls_date: props.saleReturn.sls_date || today(),
    sls_customer_id: props.saleReturn.sls_customer_id || '',
    sls_valorcam: props.saleReturn.sls_valorcam
        || (props.latest_rate ? Number(props.latest_rate.rate_usd) : 1),
    sls_coin: props.saleReturn.sls_coin || 'USD',
    sls_payment_condition: props.saleReturn.sls_payment_condition || 'cash',
    sls_credit_days: Number(props.saleReturn.sls_credit_days) || 0,
    sls_due_date: slsDueDate,
    sls_reference: props.saleReturn.sls_reference || '',
    items: (props.saleReturn.movements && props.saleReturn.movements.length > 0
        ? props.saleReturn.movements
        : []
    ).map((m: any) => ({
        id: m.id,
        mvs_pro_code: m.mvs_pro_code,
        mvs_war_id: m.mvs_war_id,
        mvs_quantity: m.mvs_quantity,
        mvs_unit_price: m.mvs_unit_price,
        mvs_unit_price_ref: m.mvs_unit_price_ref,
        mvs_pro_iva: m.mvs_pro_iva,
        _available_qty: m.mvs_quantity,
        _pro_name: m.product?.pro_name || m.mvs_pro_code,
        _war_name: m.warehouse?.war_name || m.warehouse?.id || m.mvs_war_id,
    })),
    sls_subtotal: props.saleReturn.sls_subtotal || 0,
    sls_iva: props.saleReturn.sls_iva || 0,
    sls_exento: props.saleReturn.sls_exento || 0,
    sls_total: props.saleReturn.sls_total || 0,
});

const unpaidSales = ref<any[]>([]);
const loadingSales = ref(false);
const loadingItems = ref(false);
const selectedInvoiceNumber = ref(props.saleReturn.sls_reference || '');
const isInitializing = ref(true);
const salesError = ref('');

const getWarehouseName = (id: number) => {
    const w = props.warehouses.find(wh => wh.id == id);

    return w ? w.war_name || String(w.id) : String(id);
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

const selectCustomer = (customer: any) => {
    form.sls_customer_id = customer.id;
    fetchUnpaidSales(customer.id);

    if (customer.clt_credit_days > 0) {
        form.sls_payment_condition = 'credit';
        form.sls_credit_days = customer.clt_credit_days;
        updateDueDate();
    }
};

const fetchUnpaidSales = async (customerId: number) => {
    if (!customerId) {
        return;
    }

    loadingSales.value = true;
    salesError.value = '';

    try {
        const res = await fetch(`/api/customers/${customerId}/unpaid-sales?exclude_return_id=${props.saleReturn.id}`);

        if (!res.ok) {
            const err = await res.json().catch(() => ({ error: 'Error del servidor' }));

            throw new Error(err.error || `HTTP ${res.status}`);
        }

        unpaidSales.value = await res.json();
    } catch {
        unpaidSales.value = [];
        salesError.value = 'Error al cargar facturas pendientes.';
    } finally {
        loadingSales.value = false;
    }
};

const fetchSaleItems = async (saleNumber: string) => {
    if (!saleNumber) {
        return;
    }

    loadingItems.value = true;

    try {
        const res = await fetch(`/api/sale-orders/by-number/${saleNumber}`);
        const sale = await res.json();
        const itemsRes = await fetch(
            `/api/sale-orders/${sale.id}/items?exclude_return_id=${props.saleReturn.id}`,
        );
        const originalItems = await itemsRes.json();

        if (!isInitializing.value) {
            form.sls_coin = sale.sls_coin || 'USD';
            form.sls_valorcam = Number(sale.sls_valorcam)
                || Number(props.latest_rate?.rate_usd)
                || 1;
            form.sls_payment_condition = sale.sls_payment_condition || 'cash';
            form.sls_credit_days = Number(sale.sls_credit_days) || 0;
            form.sls_due_date = sale.sls_due_date ? String(sale.sls_due_date).split(' ')[0] : form.sls_date;
        }

        if (!isInitializing.value) {
            form.items = originalItems.map((item: any) => ({
                mvs_pro_code: item.mvs_pro_code,
                mvs_war_id: item.mvs_war_id,
                mvs_quantity: item.available_qty,
                mvs_unit_price: item.mvs_unit_price,
                mvs_unit_price_ref: item.mvs_unit_price_ref,
                mvs_pro_iva: item.pro_iva || 0,
                _available_qty: item.available_qty,
                _pro_name: item.pro_name,
                _war_name: item.war_name,
            }));
        }

        calculateTotals();
    } catch {
        // ignore
    } finally {
        loadingItems.value = false;
    }
};

watch(selectedInvoiceNumber, (val) => {
    form.sls_reference = val;

    if (val && !isInitializing.value) {
        fetchSaleItems(val);
    }
});

onMounted(async () => {
    if (form.sls_customer_id) {
        await fetchUnpaidSales(form.sls_customer_id);
    }

    if (form.sls_reference) {
        await fetchSaleItems(form.sls_reference);
    }

    isInitializing.value = false;
});

const calculateTotals = () => {
    let subtotal = 0;
    let sumIva = 0;
    let exento = 0;

    for (const item of form.items) {
        const lineTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
        subtotal += lineTotal;
        const ivaRate = tx.getProductIvaRate(item.mvs_pro_code);
        sumIva += lineTotal * ivaRate;

        if (ivaRate === 0) {
            exento += lineTotal;
        }
    }

    form.sls_subtotal = Number(subtotal.toFixed(4));
    form.sls_iva = Number(sumIva.toFixed(4));
    form.sls_exento = Number(exento.toFixed(4));
    form.sls_total = Number((subtotal + sumIva).toFixed(4));
};

const getItemTotal = (item: any) => {
    const baseTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
    const ivaRate = tx.getProductIvaRate(item.mvs_pro_code);

    return (baseTotal + (baseTotal * ivaRate)).toFixed(4);
};

const submit = () => {
    form.put(`/sale-returns/${props.saleReturn.id}`, {
        preserveScroll: true,
    });
};

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
    <Head title="Editar Devolución" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Editar Devolución"
            subtitle="Registro de notas de crédito y devoluciones"
            back-url="/sale-returns"
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
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">N° Devolución</label>
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

                        <div>
                            <label class="mb-1.5 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Factura Original
                                <Loader2
                                    v-if="loadingSales"
                                    class="size-3 animate-spin text-primary"
                                />
                            </label>
                            <div v-if="loadingSales">
                                <span class="text-sm text-muted-foreground">Cargando...</span>
                            </div>
                            <p
                                v-else-if="salesError"
                                class="text-xs text-destructive"
                            >
                                {{ salesError }}
                            </p>
                            <select
                                v-else
                                v-model="selectedInvoiceNumber"
                                class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            >
                                <option value="">
                                    Seleccione factura
                                </option>
                                <option
                                    v-for="s in unpaidSales"
                                    :key="s.id"
                                    :value="s.sls_number"
                                >
                                    {{ s.sls_number }} - {{ formatDate(s.sls_date) }} ({{ s.sls_total }} {{ s.sls_coin }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors.sls_reference"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.sls_reference }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                                <Input
                                    v-model="form.sls_date"
                                    type="date"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Moneda</label>
                                <select
                                    v-model="form.sls_coin"
                                    class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
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
                            <Input
                                v-model="form.sls_valorcam"
                                type="number"
                                step="0.0001"
                                class="font-mono text-sm font-bold"
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
                    :loading="loadingItems"
                    loading-text="Cargando productos..."
                    :empty="form.items.length === 0 && !loadingItems"
                    @add="() => {}"
                >
                    <tr
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="hover:bg-muted/30 transition-colors"
                    >
                        <td class="p-3">
                            <span class="text-sm">{{ item._pro_name || item.mvs_pro_code }}</span>
                        </td>
                        <td class="p-3">
                            <span class="text-sm text-muted-foreground">
                                {{ item._war_name || getWarehouseName(item.mvs_war_id) }}
                            </span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="inline-block rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
                                {{ tx.getProductIvaLabel(item.mvs_pro_code) }}
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex items-center justify-center gap-1">
                                <input
                                    v-model="item.mvs_quantity"
                                    type="number"
                                    :max="item._available_qty"
                                    :step="tx.getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                    :min="tx.getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                    class="border-input bg-background w-20 rounded-md border py-1.5 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                    @input="calculateTotals"
                                >
                                <span
                                    v-if="item.mvs_pro_code"
                                    class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-primary"
                                >
                                    {{ tx.getUndLabel(tx.getProductUnd(item.mvs_pro_code)) }}
                                </span>
                            </div>
                            <div class="mt-0.5 text-center text-[10px] text-muted-foreground">
                                Máx: {{ item._available_qty }}
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="block w-full py-1.5 text-right font-bold">
                                {{ Number(item.mvs_unit_price).toFixed(4) }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <span class="font-mono text-xs font-medium text-primary">
                                {{ item.mvs_unit_price_ref }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-bold text-muted-foreground">
                            {{ getItemTotal(item) }}
                        </td>
                    </tr>
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
</style>
