<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { Calculator, Landmark, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import FormActions from '@/components/Form/FormActions.vue';
import FormRowDelete from '@/components/Form/FormRowDelete.vue';
import FormSection from '@/components/Form/FormSection.vue';
import ItemsTable from '@/components/Form/ItemsTable.vue';
import ConvertQuoteModal from '@/components/Quotes/ConvertQuoteModal.vue';
import StockAvailabilityAlert from '@/components/Quotes/StockAvailabilityAlert.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { useQuoteForm } from '@/composables/Quotes/useQuoteForm';
import { QUOTE_STATUS_LABEL } from '@/types/Quotes';
import type {
    QuoteAvailability,
    QuoteCustomer,
    QuoteItemForm,
    QuoteProduct,
    QuoteRate,
    QuoteStatusValue,
    QuoteWarehouse,
} from '@/types/Quotes';
import type { ColumnConfig } from '@/types/Transaction';

const props = defineProps<{
    customers: QuoteCustomer[];
    products: QuoteProduct[];
    warehouses: QuoteWarehouse[];
    rates: QuoteRate[];
    coins: string[];
    latest_rate: any;
    ivas: string[];
    quote: any;
    quote_availability?: QuoteAvailability;
}>();

const availability = computed<QuoteAvailability>(() => {
    return (
        props.quote_availability ?? {
            ok: true,
            missing: [],
        }
    );
});

const hasStockIssue = computed(() => !availability.value.ok);

const prodRef = computed(() => props.products);
const wareRef = computed(() => props.warehouses);
const ratesRef = computed(() => props.rates);
const latestRef = computed(() => props.latest_rate);

const tx = useQuoteForm(prodRef, wareRef, ratesRef, latestRef);

const valorcam = ref(Number(props.quote.sls_valorcam) || 1);

const quoteStatus = props.quote.sls_quote_status as QuoteStatusValue;
const isConverted = quoteStatus === 'converted';

const form = useForm({
    sls_date: props.quote.sls_date
        ? String(props.quote.sls_date).split(' ')[0]
        : new Date().toISOString().split('T')[0],
    sls_customer_id: String(props.quote.sls_customer_id || ''),
    sls_valorcam: valorcam.value,
    sls_coin: props.quote.sls_coin || 'USD',
    items: (props.quote.movements ?? []).map((m: any) => ({
        mvs_pro_code: m.mvs_pro_code || '',
        mvs_war_id: m.mvs_war_id || '',
        mvs_quantity: Number(m.mvs_quantity) || 1,
        mvs_unit_price_ref: Number(m.mvs_unit_price_ref) || 0,
        mvs_unit_price: Number(m.mvs_unit_price) || 0,
        mvs_pro_iva: Number(m.mvs_pro_iva) || 0,
        id: m.id || undefined,
        _pending: false,
    })) as QuoteItemForm[],
    sls_subtotal: Number(props.quote.sls_subtotal) || 0,
    sls_iva: Number(props.quote.sls_iva) || 0,
    sls_exento: Number(props.quote.sls_exento) || 0,
    sls_total: Number(props.quote.sls_total) || 0,
});

if (form.items.length === 0) {
    form.items.push(tx.blankItem());
}

const recalc = () => {
    tx.recalc(form.items, {
        setSubtotal: (v) => (form.sls_subtotal = v),
        setExento: (v) => (form.sls_exento = v),
        setIva: (v) => (form.sls_iva = v),
        setTotal: (v) => (form.sls_total = v),
    });
};

const selectCustomer = (customer: QuoteCustomer) => {
    form.sls_customer_id = String(customer.id);
};

const selectProduct = (index: number, product: QuoteProduct) => {
    const item = form.items[index];
    item._pending = true;

    const built = tx.buildItemFromRef(product, form.sls_coin, valorcam.value);
    Object.assign(item, built);

    setTimeout(() => {
        item._pending = false;
        recalc();
    }, 150);
};

const addItem = () => {
    tx.addItem(form.items, () => tx.blankItem());
};

const removeItem = (index: number) => {
    tx.removeItem(form.items, index, recalc);
};

const updatePriceFromRef = () => {
    for (const item of form.items) {
        item.mvs_unit_price = tx.getUnitPriceFromRef(
            Number(item.mvs_unit_price_ref) || 0,
            form.sls_coin,
            valorcam.value,
        );
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

const handleDateChange = () => {
    handleExchangeUpdate();
};

const handleCoinChange = () => {
    handleExchangeUpdate();
};

const handleQuantityInput = () => {
    recalc();
};

const submit = () => {
    form.sls_valorcam = valorcam.value;
    form.put(`/quotes/${props.quote.id}`, {
        preserveScroll: true,
    });
};

onMounted(() => {
    handleExchangeUpdate();
});

const convertOpen = ref(false);

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
    <Head :title="`Cotización #${props.quote.sls_number}`" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div class="flex items-start justify-between gap-4">
            <FormActions
                :title="`Cotización #${props.quote.sls_number}`"
                subtitle="Actualice los datos de la cotización"
                back-url="/quotes"
                :processing="form.processing"
                @save="submit"
            />

            <div class="flex items-center gap-3">
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold"
                    :class="{
                        'bg-blue-900/30 text-blue-400':
                            quoteStatus === 'pending',
                        'bg-emerald-900/30 text-emerald-400':
                            quoteStatus === 'approved',
                        'bg-red-900/30 text-red-400':
                            quoteStatus === 'rejected',
                        'bg-zinc-800 text-zinc-400':
                            quoteStatus === 'converted',
                    }"
                >
                    {{ QUOTE_STATUS_LABEL[quoteStatus] }}
                </span>
                <button
                    v-if="!isConverted"
                    type="button"
                    :disabled="hasStockIssue"
                    :title="
                        hasStockIssue
                            ? 'No hay existencias suficientes para convertir.'
                            : undefined
                    "
                    @click="convertOpen = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-zinc-700 disabled:text-zinc-400"
                >
                    <RefreshCw class="size-4" />
                    Convertir a Factura
                </button>
            </div>
        </div>

        <StockAvailabilityAlert
            v-if="hasStockIssue"
            :availability="availability"
        />

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
                                    @change="handleCoinChange"
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
                                    >{{
                                        Number(item.mvs_unit_price).toFixed(4)
                                    }}</span
                                >
                            </td>
                            <td class="p-3 text-right">
                                <span
                                    v-if="item._pending"
                                    class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
                                />
                                <span
                                    v-else
                                    class="font-mono text-xs font-medium text-primary"
                                    >{{ item.mvs_unit_price_ref }}</span
                                >
                            </td>
                            <td
                                class="p-3 text-right font-bold text-muted-foreground"
                            >
                                <span
                                    v-if="item._pending"
                                    class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
                                />
                                <span v-else>{{
                                    tx.getItemTotal(item).toFixed(4)
                                }}</span>
                            </td>
                            <td class="p-3 text-center">
                                <FormRowDelete @remove="removeItem(index)" />
                            </td>
                        </tr>
                    </TransitionGroup>
                </ItemsTable>
            </div>
        </div>

        <ConvertQuoteModal
            :open="convertOpen"
            :quote="{
                id: props.quote.id,
                sls_number: props.quote.sls_number,
                sls_total: props.quote.sls_total,
                sls_coin: props.quote.sls_coin,
            }"
            @update:open="convertOpen = $event"
            @converted="router.visit(`/quotes/${props.quote.id}/edit`)"
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
