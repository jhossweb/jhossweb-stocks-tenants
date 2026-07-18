<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Landmark, Calculator, Loader2 } from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormRowDelete from '@/components/Form/FormRowDelete.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { useTransactionForm } from '@/composables/Transaction/useTransactionForm';
import { formatDate, today } from '@/composables/useDate';
import type { ColumnConfig } from '@/types/Transaction';

const props = defineProps<{
    suppliers: any[];
    products: any[];
    warehouses: any[];
    rates: any[];
    coins: string[];
    latest_rate: any;
    ivas: string[];
    purchaseReturn: any;
}>();

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useTransactionForm(prodRef, wareRef, ratesRef, latestRef);

const valorcam = ref(Number(props.purchaseReturn.invoice_valorcam) || 1);

const form = useForm({
    invoice_number: props.purchaseReturn.invoice_number || '',
    invoice_date: props.purchaseReturn.invoice_date
        ? String(props.purchaseReturn.invoice_date).split(' ')[0]
        : today(),
    invoice_supplier_id: String(props.purchaseReturn.invoice_supplier_id || ''),
    invoice_valorcam: valorcam.value,
    invoice_coin: props.purchaseReturn.invoice_coin || 'USD',
    items: (props.purchaseReturn.movements && props.purchaseReturn.movements.length > 0
        ? props.purchaseReturn.movements
        : []
    ).map((m: any) => ({
        mov_pro_code: m.mov_pro_code || '',
        mov_war_id: m.mov_war_id || '',
        mov_quantity: Number(m.mov_quantity) || 1,
        mov_quantity_max: 999999,
        mov_unit_cost_ref: Number(m.mov_unit_cost_ref) || 0,
        mov_unit_cost: Number(m.mov_unit_cost) || 0,
        mov_pro_iva: Number(m.mov_pro_iva) || 0,
        id: m.id || undefined,
        _pending: false,
    })),
    invoice_subtotal: Number(props.purchaseReturn.invoice_subtotal) || 0,
    invoice_iva: Number(props.purchaseReturn.invoice_iva) || 0,
    invoice_exento: Number(props.purchaseReturn.invoice_exento) || 0,
    invoice_total: Number(props.purchaseReturn.invoice_total) || 0,
    invoice_reference: props.purchaseReturn.invoice_reference || '',
});

if (form.items.length === 0) {
    form.items.push(createBlankItem());
}

const unpaidInvoices = ref<any[]>([]);
const selectedInvoiceId = ref('');
const isLoadingInvoices = ref(false);
const isLoadingItems = ref(false);
const isInitializing = ref(false);

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

const fetchUnpaidInvoices = async (supplierId: string | number) => {
    if (!supplierId) {
        unpaidInvoices.value = [];

        return;
    }

    isLoadingInvoices.value = true;

    try {
        const response = await fetch(`/api/suppliers/${supplierId}/unpaid-invoices`);
        unpaidInvoices.value = await response.json();
    } catch (error) {
        console.error('Error fetching invoices:', error);
    } finally {
        isLoadingInvoices.value = false;
    }
};

const fetchInvoiceItems = async (invoiceId: string | number) => {
    if (!invoiceId) {
        return;
    }

    isLoadingItems.value = true;

    try {
        const response = await fetch(`/api/purchase-orders/${invoiceId}/items`);
        const items = await response.json();

        const invoice = unpaidInvoices.value.find(i => i.id == invoiceId);

        if (invoice) {
            form.invoice_coin = invoice.invoice_coin;
            valorcam.value = Number(invoice.invoice_valorcam);
        }

        form.items = items.map((item: any) => ({
            mov_pro_code: item.mov_pro_code,
            mov_war_id: item.mov_war_id,
            mov_quantity: Number(item.mov_quantity_remaining),
            mov_quantity_max: Number(item.mov_quantity_remaining),
            mov_unit_cost: Number(item.mov_unit_cost),
            mov_unit_cost_ref: Number(item.mov_unit_cost_ref),
            mov_pro_iva: Number(item.mov_pro_iva),
            _pending: false,
        }));

        recalc();
    } catch (error) {
        console.error('Error fetching items:', error);
    } finally {
        isLoadingItems.value = false;
    }
};

watch(() => form.invoice_supplier_id, (newVal) => {
    selectedInvoiceId.value = '';
    fetchUnpaidInvoices(newVal);
});

watch(selectedInvoiceId, (newVal) => {
    const invoice = unpaidInvoices.value.find(i => i.id == newVal);
    form.invoice_reference = invoice ? invoice.invoice_number : '';

    if (newVal && !isInitializing.value) {
        fetchInvoiceItems(newVal);
    }
});

const selectSupplier = (supplier: any) => {
    form.invoice_supplier_id = supplier.id;
};

const selectProduct = (index: number, product: any) => {
    const items = form.items as any[];
    items[index].mov_pro_code = product.pro_code;
    recalc();
};

const addItem = () => {
    tx.addItem(form.items, createBlankItem);
};

const removeItem = (index: number) => {
    tx.removeItem(form.items, index, recalc);
};

const handleQuantityInput = () => {
    recalc();
};

const handleCostInput = (index: number) => {
    tx.updateCostRef(form.invoice_coin, valorcam.value, form.items[index]);
};

const handleExchangeUpdate = () => {
    tx.updateExchangeRate(form.invoice_date, form.invoice_coin, valorcam, recalc);
};

const submit = () => {
    form.items = form.items.filter((item: any) => item.mov_pro_code && item.mov_war_id);

    if (form.items.length === 0) {
        alert('Debe agregar al menos un producto válido');

        return;
    }

    const invalidItem = form.items.find(
        (item: any) => Number(item.mov_quantity) > Number(item.mov_quantity_max),
    );

    if (invalidItem) {
        alert('La cantidad a devolver de un producto supera lo disponible en la factura.');

        return;
    }

    form.invoice_valorcam = valorcam.value;
    form.put(`/purchase-returns/${props.purchaseReturn.id}`, {
        preserveScroll: true,
    });
};

onMounted(async () => {
    isInitializing.value = true;

    let originalInvoiceId = null;

    if (form.invoice_reference) {
        try {
            const respInv = await fetch(`/api/purchase-orders/by-number/${form.invoice_reference}`);
            const originalInvoice = await respInv.json();

            if (originalInvoice) {
                originalInvoiceId = originalInvoice.id;
                selectedInvoiceId.value = originalInvoice.id;
            }
        } catch (e) {
            console.error('Error al obtener factura original:', e);
        }
    }

    if (form.invoice_supplier_id) {
        let url = `/api/suppliers/${form.invoice_supplier_id}/unpaid-invoices`;

        if (originalInvoiceId) {
            url += `?current_invoice_id=${originalInvoiceId}`;
        }

        isLoadingInvoices.value = true;

        try {
            const response = await fetch(url);
            unpaidInvoices.value = await response.json();
        } catch (error) {
            console.error('Error fetching invoices:', error);
        } finally {
            isLoadingInvoices.value = false;
        }
    }

    if (selectedInvoiceId.value) {
        try {
            const respItems = await fetch(
                `/api/purchase-orders/${selectedInvoiceId.value}/items?exclude_return_id=${props.purchaseReturn.id}`,
            );
            const availableItems = await respItems.json();

            form.items.forEach((item: any) => {
                const match = availableItems.find(
                    (ai: any) =>
                        ai.mov_pro_code === item.mov_pro_code
                        && ai.mov_war_id === item.mov_war_id,
                );

                if (match) {
                    item.mov_quantity_max =
                        Number(match.mov_quantity_remaining) + Number(item.mov_quantity);
                } else {
                    item.mov_quantity_max = Number(item.mov_quantity);
                }
            });
        } catch (e) {
            console.error('Error al actualizar cantidades máximas:', e);
        }
    }

    isInitializing.value = false;
    handleExchangeUpdate();
});

function createBlankItem() {
    return {
        mov_pro_code: '',
        mov_war_id: '',
        mov_quantity: 1,
        mov_quantity_max: 999999,
        mov_unit_cost_ref: 0,
        mov_unit_cost: 0,
        mov_pro_iva: 0,
        _pending: false,
    };
}

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
    <Head title="Editar Devolución de Compra" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <FormActions
            title="Editar Devolución de Compra"
            subtitle="Modificar salida de mercancía por devolución"
            back-url="/purchase-returns"
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
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">N° Documento</label>
                            <Input
                                v-model="form.invoice_number"
                                type="text"
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

                        <div v-if="form.invoice_supplier_id">
                            <label class="mb-1.5 flex items-center gap-2 text-sm font-medium text-muted-foreground">
                                Factura Original
                                <Loader2
                                    v-if="isLoadingInvoices"
                                    class="size-3 animate-spin text-primary"
                                />
                            </label>
                            <select
                                v-model="selectedInvoiceId"
                                class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            >
                                <option value="">
                                    Seleccione factura para devolución
                                </option>
                                <option
                                    v-for="inv in unpaidInvoices"
                                    :key="inv.id"
                                    :value="inv.id"
                                >
                                    #{{ inv.invoice_number }} - {{ formatDate(inv.invoice_date) }} (${{ inv.invoice_total }})
                                </option>
                            </select>
                            <p
                                v-if="unpaidInvoices.length === 0 && !isLoadingInvoices"
                                class="mt-1 text-[10px] text-muted-foreground"
                            >
                                No hay facturas pendientes para este proveedor
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                                <Input
                                    v-model="form.invoice_date"
                                    type="date"
                                    @change="handleExchangeUpdate"
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
                                @input="recalc"
                            />
                        </div>
                    </div>
                </FormSection>

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

            <div class="lg:col-span-8">
                <ItemsTable
                    :columns="columns"
                    :loading="isLoadingItems"
                    loading-text="Cargando productos..."
                    :empty="form.items.length === 0 && !isLoadingItems"
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
                                <p
                                    v-if="form.errors[`items.${index}.mov_pro_code`]"
                                    class="text-[10px] text-destructive"
                                >
                                    {{ form.errors[`items.${index}.mov_pro_code`] }}
                                </p>
                            </td>
                            <td class="p-3">
                                <select
                                    v-model="item.mov_war_id"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                >
                                    <option value="">
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
                                <p
                                    v-if="form.errors[`items.${index}.mov_war_id`]"
                                    class="text-[10px] text-destructive"
                                >
                                    {{ form.errors[`items.${index}.mov_war_id`] }}
                                </p>
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
                                        :max="item.mov_quantity_max"
                                        :step="tx.getProductUnd(item.mov_pro_code) === 'und' ? 1 : 0.01"
                                        :min="tx.getProductUnd(item.mov_pro_code) === 'und' ? 1 : 0.01"
                                        class="border-input bg-background w-20 rounded-md border py-1.5 text-center text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                                        @input="handleQuantityInput"
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
                                <p
                                    v-if="form.errors[`items.${index}.mov_quantity`]"
                                    class="text-[10px] text-destructive"
                                >
                                    {{ form.errors[`items.${index}.mov_quantity`] }}
                                </p>
                                <p
                                    v-if="Number(item.mov_quantity) > Number(item.mov_quantity_max)"
                                    class="mt-1 text-[10px] text-destructive"
                                >
                                    Disp. max: {{ item.mov_quantity_max }}
                                </p>
                            </td>
                            <td class="p-3">
                                <input
                                    v-model="item.mov_unit_cost"
                                    type="number"
                                    step="0.0001"
                                    class="w-full border-none bg-transparent text-right text-sm font-bold focus:ring-0"
                                    @input="handleCostInput(index)"
                                >
                                <p
                                    v-if="form.errors[`items.${index}.mov_unit_cost`]"
                                    class="text-[10px] text-destructive"
                                >
                                    {{ form.errors[`items.${index}.mov_unit_cost`] }}
                                </p>
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
