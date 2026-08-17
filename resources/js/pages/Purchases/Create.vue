<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    Landmark,
    Calculator,
    CheckCircle2,
    AlertCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref, shallowRef } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
import PaymentSection from '@/components/payments/PaymentSection.vue';
import PurchaseItemRow from '@/components/Purchases/PurchaseItemRow.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { useTransactionForm } from '@/composables/Transaction/useTransactionForm';
import { today } from '@/composables/useDate';
import type {
    ColumnConfig,
    LatestRate,
    Product,
    Rate,
    Supplier,
    TransactionItem,
    Warehouse,
} from '@/types/Transaction';

const props = defineProps<{
    suppliers: Supplier[];
    products: Product[];
    warehouses: Warehouse[];
    rates: Rate[];
    coins: string[];
    latest_rate: LatestRate | null;
    bankAccounts: any[];
    methods: string[];
}>();

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useTransactionForm(prodRef, wareRef, ratesRef, latestRef);

const valorcam = shallowRef(
    props.latest_rate ? Number(props.latest_rate.rate_usd) : 1,
);

let itemKeyCounter = 0;

function nextItemKey(): string {
    return `k${++itemKeyCounter}`;
}

function createBlankItem(): TransactionItem {
    return {
        mov_pro_code: '',
        mov_war_id: '',
        mov_quantity: 1,
        mov_unit_cost_ref: 0,
        mov_unit_cost: 0,
        mov_pro_iva: 0,
        _pending: false,
        _key: nextItemKey(),
    };
}

const form = useForm({
    invoice_number: '',
    invoice_date: today(),
    invoice_supplier_id: '',
    invoice_valorcam: 1,
    invoice_coin: 'USD',
    invoice_payment_condition: 'cash',
    invoice_credit_days: 0,
    invoice_due_date: today(),
    items: [] as TransactionItem[],
    invoice_subtotal: 0,
    invoice_iva: 0,
    invoice_exento: 0,
    invoice_total: 0,
    payment: {
        payment_date: today(),
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

form.invoice_valorcam = valorcam.value;

if (form.items.length === 0) {
    form.items.push(createBlankItem());
}

const step = ref<'ticket' | 'payment'>('ticket');
const clientError = ref('');

const isCash = computed(() => form.invoice_payment_condition === 'cash');

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

const recalc = () => {
    tx.calculateTotals(
        form.items,
        (v) => {
            form.invoice_subtotal = v;
        },
        (v) => {
            form.invoice_iva = v;
        },
        (v) => {
            form.invoice_exento = v;
        },
        (v) => {
            form.invoice_total = v;
        },
    );
};

const updateDueDate = () => {
    if (!form.invoice_date) {
        return;
    }

    const parts = form.invoice_date.split('-');
    const date = new Date(
        Number(parts[0]),
        Number(parts[1]) - 1,
        Number(parts[2]),
    );
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

const selectSupplier = (supplier: Supplier) => {
    form.invoice_supplier_id = String(supplier.id);

    const creditDays = Number(supplier.supplier_credit_days) || 0;

    if (creditDays > 0) {
        form.invoice_payment_condition = 'credit';
        form.invoice_credit_days = creditDays;
        updateDueDate();
    }
};

const selectProduct = (index: number, product: Product) => {
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
    tx.updateExchangeRate(
        form.invoice_date,
        form.invoice_coin,
        valorcam,
        recalc,
    );
};

const validateTicket = (): string => {
    if (!form.invoice_number.trim()) {
        return 'Ingrese el número de factura.';
    }

    if (!form.invoice_supplier_id) {
        return 'Seleccione un proveedor.';
    }

    if (form.items.length === 0) {
        return 'Agregue al menos un producto.';
    }

    for (const item of form.items) {
        if (!item.mov_pro_code) {
            return 'Todos los productos deben estar seleccionados.';
        }

        if (!item.mov_war_id) {
            return 'Todos los productos deben tener almacén asignado.';
        }

        if (Number(item.mov_quantity) <= 0) {
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

    form.payment.payment_date = today();
    form.payment.payments = [
        {
            payment_currency: form.invoice_coin,
            payment_rate: '1',
            bank_account_id: '',
            payment_method: props.methods[0] || '',
            payment_reference: '',
            payment_description: '',
            payment_amount: Number(form.invoice_total.toFixed(4)),
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

    form.invoice_valorcam = valorcam.value;
    form.post('/purchases', {
        preserveScroll: true,
    });
};

onMounted(() => {
    form.items.forEach((item) => {
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

        <!-- Validation errors -->
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
            <!-- Sidebar: Config + Totals -->
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
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Proveedor</label
                            >
                            <SelectorModal
                                title="Seleccionar Proveedor"
                                description="Busque y seleccione un proveedor de la lista"
                                :items="suppliers"
                                :search-fields="[
                                    'supplier_name',
                                    'name',
                                    'supplier_rif',
                                    'rif',
                                ]"
                                :display-fields="[
                                    { label: 'Nombre', key: 'supplier_name' },
                                    { label: 'RIF', key: 'supplier_rif' },
                                ]"
                                :trigger-label="
                                    tx.getSupplierName(
                                        suppliers,
                                        form.invoice_supplier_id,
                                    )
                                "
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
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Fecha</label
                                >
                                <Input
                                    v-model="form.invoice_date"
                                    type="date"
                                    class="w-full"
                                    @change="handleDateChange"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Moneda</label
                                >
                                <select
                                    v-model="form.invoice_coin"
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
                                    v-model="form.invoice_payment_condition"
                                    class="flex h-10 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
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
                                <label
                                    class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                    >Días Crédito</label
                                >
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
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Fecha Vencimiento</label
                            >
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
                                class="w-full font-mono text-sm font-bold"
                                @input="recalc"
                            />
                        </div>
                    </div>
                </FormSection>

                <!-- Totals Card -->
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
                            <span>{{ form.invoice_subtotal }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>EXENTO</span>
                            <span>{{ form.invoice_exento }}</span>
                        </div>
                        <div
                            class="flex justify-between text-xs font-medium text-primary-foreground/80"
                        >
                            <span>IVA</span>
                            <span>{{ form.invoice_iva }}</span>
                        </div>
                        <div
                            class="flex items-end justify-between border-t border-primary-foreground/10 pt-3"
                        >
                            <span class="text-sm font-bold uppercase"
                                >Total {{ form.invoice_coin }}</span
                            >
                            <span class="text-3xl font-black tabular-nums">{{
                                form.invoice_total
                            }}</span>
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
                        <PurchaseItemRow
                            v-for="(item, index) in form.items"
                            :key="item._key ?? index"
                            v-model="form.items[index]"
                            :catalog="tx"
                            :products="products"
                            :warehouses="warehouses"
                            @select-product="
                                (p: Product) => selectProduct(index, p)
                            "
                            @quantity-input="handleQuantityInput(index)"
                            @cost-input="handleCostInput(index)"
                            @remove="removeItem(index)"
                        />
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
                :doc-currency="form.invoice_coin"
                :doc-total="Number(form.invoice_total) || 0"
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
                                    : 'Confirmar Compra y Pago'
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
