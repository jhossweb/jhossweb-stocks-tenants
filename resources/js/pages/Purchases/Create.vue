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
    suppliers: any[];
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
    invoice_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    invoice_supplier_id: '',
    invoice_valorcam: 1,
    invoice_coin: 'USD',
    invoice_payment_condition: 'cash',
    invoice_credit_days: 0,
    invoice_due_date: new Date().toISOString().split('T')[0],
    items: [] as any[],
    invoice_subtotal: 0,
    invoice_iva: 0,
    invoice_exento: 0,
    invoice_total: 0,
});

form.invoice_valorcam = valorcam.value;

const createBlankItem = () => ({
    mov_pro_code: '',
    mov_war_id: '',
    mov_quantity: 1,
    mov_unit_cost_ref: 0,
    mov_unit_cost: 0,
    mov_pro_iva: 0,
    _pending: false,
});

if (form.items.length === 0) {
    form.items.push(createBlankItem());
}

const recalc = () => {
    tx.calculateTotals(
        form.items,
        v => {
            form.invoice_subtotal = v;
        },
        v => {
            form.invoice_iva = v;
        },
        v => {
            form.invoice_exento = v;
        },
        v => {
            form.invoice_total = v;
        },
    );
};

const updateDueDate = () => {
    if (!form.invoice_date) {
return;
}

    const parts = form.invoice_date.split('-');
    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    date.setDate(date.getDate() + Number(form.invoice_credit_days));
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    form.invoice_due_date = `${y}-${m}-${d}`;
};

const handleConditionChange = () => {
    if (form.invoice_payment_condition === 'cash') {
        form.invoice_credit_days = 0;
    }

    updateDueDate();
};

const handleDateChange = () => {
    handleExchangeUpdate();
    updateDueDate();
};

const selectSupplier = (supplier: any) => {
    form.invoice_supplier_id = supplier.id;

    if (supplier.supplier_credit_days > 0) {
        form.invoice_payment_condition = 'credit';
        form.invoice_credit_days = supplier.supplier_credit_days;
        updateDueDate();
    }
};

const selectProduct = (index: number, product: any) => {
    const item = form.items[index];
    item.mov_pro_code = product.pro_code;
    item._pending = true;

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

const handleQuantityInput = (index: number) => {
    tx.updateCostRef(form.invoice_coin, valorcam.value, form.items[index]);
    recalc();
};

const handleCostInput = (index: number) => {
    tx.updateCostRef(form.invoice_coin, valorcam.value, form.items[index]);
    recalc();
};

const handleExchangeUpdate = () => {
    tx.updateExchangeRate(form.invoice_date, form.invoice_coin, valorcam, recalc);
};

const submit = () => {
    form.invoice_valorcam = valorcam.value;
    form.post('/purchases', {
        preserveScroll: true,
    });
};

onMounted(() => {
    form.items.forEach(item => {
        tx.updateCostRef(form.invoice_coin, valorcam.value, item);
    });
    updateDueDate();
});

const columns: ColumnConfig[] = [
    { key: 'product', label: 'Producto' },
    { key: 'warehouse', label: 'Almacén' },
    { key: 'iva', label: 'IVA', align: 'center', class: 'text-primary' },
    { key: 'qty', label: 'Cant.', align: 'center', width: '6rem' },
    { key: 'cost', label: 'Costo Unit.', align: 'right' },
    { key: 'ref', label: 'Ref ($)', align: 'right', class: 'text-primary' },
    { key: 'total', label: 'Total', align: 'right' },
];
</script>

<template>
    <Head title="Nueva Compra" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Nueva Compra"
            subtitle="Registro de facturas e inventario"
            back-url="/purchases"
            :processing="form.processing"
            @save="submit"
        />

        <!-- Validation errors -->
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
            <!-- Sidebar: Config + Totals -->
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
                                v-model="form.invoice_number"
                                type="text"
                                class="w-full"
                            />
                            <p
                                v-if="form.errors.invoice_number"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.invoice_number }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Proveedor</label>
                            <SelectorModal
                                title="Seleccionar Proveedor"
                                description="Busque y seleccione un proveedor de la lista"
                                :items="suppliers"
                                :search-fields="['supplier_name', 'name', 'supplier_rif', 'rif']"
                                :display-fields="[
                                    { label: 'Nombre', key: 'supplier_name' },
                                    { label: 'RIF', key: 'supplier_rif' },
                                ]"
                                :trigger-label="tx.getSupplierName(suppliers, form.invoice_supplier_id)"
                                :selected-value="form.invoice_supplier_id"
                                @select="selectSupplier"
                            />
                            <p
                                v-if="form.errors.invoice_supplier_id"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.invoice_supplier_id }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                                <Input
                                    v-model="form.invoice_date"
                                    type="date"
                                    class="w-full"
                                    @change="handleDateChange"
                                />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Moneda</label>
                                <select
                                    v-model="form.invoice_coin"
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
                                    v-model="form.invoice_payment_condition"
                                    class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    @change="handleConditionChange"
                                >
                                    <option value="cash">Contado</option>
                                    <option value="credit">Crédito</option>
                                </select>
                                <p
                                    v-if="form.errors.invoice_payment_condition"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ form.errors.invoice_payment_condition }}
                                </p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Días Crédito</label>
                                <Input
                                    v-model="form.invoice_credit_days"
                                    type="number"
                                    min="0"
                                    max="365"
                                    @input="updateDueDate"
                                />
                                <p
                                    v-if="form.errors.invoice_credit_days"
                                    class="mt-1 text-xs text-destructive"
                                >
                                    {{ form.errors.invoice_credit_days }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha Vencimiento</label>
                            <Input
                                v-model="form.invoice_due_date"
                                type="date"
                                disabled
                            />
                            <p
                                v-if="form.errors.invoice_due_date"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.invoice_due_date }}
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
                                class="w-full font-mono text-sm font-bold"
                                @input="recalc"
                            />
                        </div>
                    </div>
                </FormSection>

                <!-- Totals Card -->
                <div class="relative overflow-hidden rounded-xl bg-primary p-6 text-primary-foreground shadow-lg">
                    <Calculator class="text-primary-foreground/10 absolute -bottom-4 -right-4 size-24" />
                    <div class="relative z-10 flex flex-col gap-3">
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>SUBTOTAL</span>
                            <span>{{ form.invoice_subtotal }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>EXENTO</span>
                            <span>{{ form.invoice_exento }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-medium text-primary-foreground/80">
                            <span>IVA</span>
                            <span>{{ form.invoice_iva }}</span>
                        </div>
                        <div class="flex items-end justify-between border-t border-primary-foreground/10 pt-3">
                            <span class="text-sm font-bold uppercase">Total {{ form.invoice_coin }}</span>
                            <span class="text-3xl font-black tabular-nums">{{ form.invoice_total }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
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
                                    ]"
                                    :trigger-label="tx.getProductName(item.mov_pro_code)"
                                    :selected-value="item.mov_pro_code"
                                    trigger-class="bg-transparent border-none hover:bg-muted/50 w-full"
                                    @select="(p: any) => selectProduct(index, p)"
                                />
                            </td>
                            <td class="p-3">
                                <select
                                    v-model="item.mov_war_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                >
                                    <option
                                        value=""
                                        class="bg-card"
                                    >
                                        Seleccione...
                                    </option>
                                    <option
                                        v-for="w in warehouses"
                                        :key="w.id"
                                        :value="w.id"
                                        class="bg-card"
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
                                    {{ tx.getProductIvaLabel(item.mov_pro_code) }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-center gap-1">
                                    <input
                                        v-model="item.mov_quantity"
                                        type="number"
                                        :step="tx.getProductUnd(item.mov_pro_code) === 'und' ? 1 : 0.01"
                                        :min="tx.getProductUnd(item.mov_pro_code) === 'und' ? 1 : 0.01"
                                        class="border-input bg-background w-20 rounded-md border py-1.5 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                        @input="handleQuantityInput(index)"
                                    >
                                    <span
                                        v-if="item.mov_pro_code"
                                        class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-primary"
                                    >
                                        {{ tx.getUndLabel(tx.getProductUnd(item.mov_pro_code)) }}
                                    </span>
                                </div>
                                <div
                                    v-if="item.mov_pro_code && ['mts', 'kg', 'm2'].includes(tx.getProductUnd(item.mov_pro_code)) && tx.getProductConversion(item.mov_pro_code) > 1"
                                    class="mt-0.5 text-[10px] text-amber-500"
                                >
                                    ×{{ tx.getProductConversion(item.mov_pro_code) }}
                                    {{ tx.getUndLabel(tx.getProductUnd(item.mov_pro_code)) }}
                                </div>
                            </td>
                            <td class="p-3">
                                <div class="flex items-center gap-1">
                                    <input
                                        v-model="item.mov_unit_cost"
                                        type="number"
                                        step="0.0001"
                                        class="w-full border-none bg-transparent text-right text-sm font-bold focus:ring-0"
                                        @input="handleCostInput(index)"
                                    >
                                    <span
                                        v-if="item._pending"
                                        class="bg-muted inline-block size-4 animate-pulse rounded"
                                    />
                                </div>
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
                                    {{ item.mov_unit_cost_ref }}
                                </span>
                            </td>
                            <td class="p-3 text-right font-bold text-muted-foreground">
                                <span
                                    v-if="item._pending"
                                    class="bg-muted inline-block h-4 w-16 animate-pulse rounded"
                                />
                                <span v-else>{{ tx.getItemTotal(item) }}</span>
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
/* Remove number input arrows */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type='number'] {
    -moz-appearance: textfield;
}

/* Date picker dark mode */
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Row transition */
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
