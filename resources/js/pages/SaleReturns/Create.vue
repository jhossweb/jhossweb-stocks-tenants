<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Landmark, Calculator, Loader2, Search } from 'lucide-vue-next';
import { ref, watch, onMounted } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';
import { formatDate, today } from '@/composables/useDate';

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

const form = useForm({
    sls_number: '',
    sls_date: today(),
    sls_customer_id: '',
    sls_valorcam: props.latest_rate ? Number(props.latest_rate.rate_usd) : 1,
    sls_coin: 'USD',
    sls_reference: '',
    items: [] as any[],
    sls_subtotal: 0,
    sls_iva: 0,
    sls_exento: 0,
    sls_total: 0,
});

const unpaidSales = ref<any[]>([]);
const originalItems = ref<any[]>([]);
const loadingSales = ref(false);
const loadingItems = ref(false);
const selectedInvoiceNumber = ref('');
const salesError = ref('');

const getCustomerName = (id: string | number) => {
    const c = props.customers.find(c => c.id == id);

    return c ? c.clt_name : 'Seleccione cliente';
};

const getProductName = (code: string) => {
    const p = props.products.find(p => p.pro_code === code);

    return p ? `${p.pro_name}` : 'Seleccione producto';
};

const getWarehouseName = (id: number) => {
    const w = props.warehouses.find(wh => wh.id == id);

    return w ? w.war_name || w.id : id;
};

const selectCustomer = (customer: any) => {
    form.sls_customer_id = customer.id;
    form.sls_reference = '';
    selectedInvoiceNumber.value = '';
    form.items = [];
    calculateTotals();
    fetchUnpaidSales(customer.id);
};

const fetchUnpaidSales = async (customerId: number) => {
    if (!customerId) {
return;
}

    loadingSales.value = true;
    salesError.value = '';

    try {
        const res = await fetch(`/api/customers/${customerId}/unpaid-sales`);

        if (! res.ok) {
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
        const itemsRes = await fetch(`/api/sale-orders/${sale.id}/items`);
        originalItems.value = await itemsRes.json();

        form.sls_coin = sale.sls_coin || 'USD';
        form.sls_valorcam = Number(sale.sls_valorcam) || Number(props.latest_rate?.rate_usd) || 1;

        form.items = originalItems.value.map((item: any) => ({
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

        calculateTotals();
    } catch {
        originalItems.value = [];
        form.items = [];
    } finally {
        loadingItems.value = false;
    }
};

watch(selectedInvoiceNumber, (val) => {
    form.sls_reference = val;

    if (val) {
        fetchSaleItems(val);
    } else {
        form.items = [];
        originalItems.value = [];
    }
});

const getProductUnd = (code: string) => {
    const p = props.products.find(p => p.pro_code === code);

    return p ? p.pro_und : '';
};

const getUndLabel = (und: string) => ({
    'und': 'Und',
    'mts': 'Mts',
    'kg': 'Kg',
    'm2': 'm²'
}[und] || und);

const getProductIvaRate = (proCode: string) => {
    const product = props.products.find(p => p.pro_code === proCode);

    return product && product.pro_iva ? Number(product.pro_iva) / 100 : 0;
};

const getProductIvaLabel = (proCode: string) => {
    const product = props.products.find(p => p.pro_code === proCode);

    if (!product || !product.pro_iva) {
return '-';
}

    return product.pro_iva === '0' ? 'EX' : `${product.pro_iva}%`;
};

const calculateTotals = () => {
    let subtotal = 0;
    let sumIva = 0;
    let exento = 0;

    form.items.forEach((item: any) => {
        const lineTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
        subtotal += lineTotal;
        const ivaRate = getProductIvaRate(item.mvs_pro_code);
        sumIva += lineTotal * ivaRate;

        if (ivaRate === 0) {
            exento += lineTotal;
        }
    });

    form.sls_subtotal = Number(subtotal.toFixed(4));
    form.sls_iva = Number(sumIva.toFixed(4));
    form.sls_exento = Number(exento.toFixed(4));
    form.sls_total = Number((subtotal + sumIva).toFixed(4));
};

const getItemTotal = (item: any) => {
    const baseTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
    const ivaRate = getProductIvaRate(item.mvs_pro_code);

    return (baseTotal + (baseTotal * ivaRate)).toFixed(2);
};

const submit = () => {
    form.post('/sale-returns', {
        preserveScroll: true,
        onSuccess: () => console.log('Éxito!'),
        onError: (errors) => console.log('Errores:', errors),
    });
};

const normalizeDate = (d: string) => d.substring(0, 10);

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
        return null;
    }

    const normalized = normalizeDate(date);
    const exactRate = props.rates.find((r: any) => normalizeDate(r.rate_date) === normalized);

    if (exactRate) {
        return exactRate;
    }

    const pastRates = props.rates
        .filter((r: any) => normalizeDate(r.rate_date) < normalized)
        .sort((a: any, b: any) => normalizeDate(b.rate_date).localeCompare(normalizeDate(a.rate_date)));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const updateExchangeRate = () => {
    const selectedRate = getBestRateForDate(form.sls_date);

    if (!selectedRate) {
        return;
    }

    if (form.sls_coin === 'USD' || form.sls_coin === 'BS' || form.sls_coin === 'VES') {
        form.sls_valorcam = Number(selectedRate.rate_usd);
    } else if (form.sls_coin === 'COP') {
        form.sls_valorcam = Number(selectedRate.rate_cop);
    } else if (form.sls_coin === 'EUR') {
        form.sls_valorcam = Number(selectedRate.rate_eur);
    }
};
</script>

<template>
    <Head title="Nueva Devolución" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/sale-returns" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Nueva Devolución</h1>
                        <p class="text-xs text-zinc-500">Registro de notas de crédito y devoluciones</p>
                    </div>
                </div>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg shadow-indigo-900/20"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                    <Save v-else class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : 'Guardar' }}
                </button>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-900/20 border border-red-500/50 p-4 rounded-xl">
                <p class="text-sm text-red-400 font-bold mb-1">Hay problemas con el envío:</p>
                <ul class="list-disc list-inside text-xs text-red-300">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-zinc-900 rounded-xl border border-zinc-800 p-6 shadow-sm space-y-5">
                        <div class="flex items-center gap-2 text-indigo-400 font-bold text-xs uppercase tracking-widest">
                            <Landmark class="w-4 h-4" /> Configuración General
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">N° Devolución</label>
                            <Input type="text" v-model="form.sls_number" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg" />
                            <p v-if="form.errors.sls_number" class="text-xs text-red-500 mt-1">{{ form.errors.sls_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Cliente</label>
                            <SelectorModal
                                title="Seleccionar Cliente"
                                description="Busque y seleccione un cliente de la lista"
                                :items="customers"
                                :searchFields="['clt_name', 'clt_email', 'clt_phone']"
                                :displayFields="[
                                    { label: 'Nombre', key: 'clt_name' },
                                    { label: 'Email', key: 'clt_email' }
                                ]"
                                :triggerLabel="getCustomerName(form.sls_customer_id)"
                                :selectedValue="form.sls_customer_id"
                                @select="selectCustomer"
                            />
                            <p v-if="form.errors.sls_customer_id" class="text-xs text-red-500 mt-1">{{ form.errors.sls_customer_id }}</p>
                        </div>

                        <div v-if="form.sls_customer_id">
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Factura Original</label>
                            <div v-if="loadingSales" class="text-zinc-500 text-sm flex items-center gap-2">
                                <Loader2 class="w-4 h-4 animate-spin" /> Cargando...
                            </div>
                            <p v-else-if="salesError" class="text-xs text-red-500">{{ salesError }}</p>
                            <select v-else v-model="selectedInvoiceNumber" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm">
                                <option value="">Seleccione factura</option>
                                <option v-for="s in unpaidSales" :key="s.id" :value="s.sls_number">
                                    {{ s.sls_number }} - {{ formatDate(s.sls_date) }} ({{ s.sls_total }} {{ s.sls_coin }})
                                </option>
                            </select>
                            <p v-if="form.errors.sls_reference" class="text-xs text-red-500 mt-1">{{ form.errors.sls_reference }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Fecha</label>
                                <Input type="date" v-model="form.sls_date" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Moneda</label>
                                <select v-model="form.sls_coin" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm">
                                    <option v-for="c in coins" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-900/10 rounded-lg border border-indigo-500/20">
                            <label class="block text-xs font-bold text-indigo-400 uppercase mb-1.5">Tasa de Cambio</label>
                            <Input type="number" step="0.0001" v-model="form.sls_valorcam" class="w-full bg-zinc-950 border-zinc-700 rounded-md text-sm font-mono font-bold text-indigo-300" />
                        </div>
                    </div>

                    <div class="bg-indigo-600 text-white rounded-xl p-6 shadow-xl relative overflow-hidden">
                        <Calculator class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10" />
                        <div class="relative z-10 space-y-3">
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>SUBTOTAL</span> <span>{{ form.sls_subtotal }}</span></div>
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>EXENTO</span> <span>{{ form.sls_exento }}</span></div>
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>IVA</span> <span>{{ form.sls_iva }}</span></div>
                            <div class="pt-3 border-t border-white/10 flex justify-between items-end">
                                <span class="text-white font-bold text-sm uppercase">Total {{ form.sls_coin }}</span>
                                <span class="text-3xl font-black tabular-nums">{{ form.sls_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                        <span class="text-xs font-bold uppercase text-zinc-500 tracking-wider">
                            Detalle de Productos
                            <span v-if="loadingItems" class="ml-2 text-indigo-400">
                                <Loader2 class="w-3 h-3 inline animate-spin" />
                            </span>
                        </span>
                    </div>

                    <div v-if="form.items.length === 0 && !loadingItems" class="p-12 text-center">
                        <Search class="w-12 h-12 mx-auto text-zinc-600 mb-4" />
                        <p class="text-zinc-500">Seleccione una factura para cargar sus productos</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                                <tr>
                                    <th class="px-4 py-4 text-left">Producto</th>
                                    <th class="px-4 py-4 text-left">Almacén</th>
                                    <th class="px-4 py-4 text-center text-indigo-400">IVA</th>
                                    <th class="px-4 py-4 text-center w-24">Cant.</th>
                                    <th class="px-4 py-4 text-right">Precio Unit.</th>
                                    <th class="px-4 py-4 text-right text-indigo-400">Ref ($)</th>
                                    <th class="px-4 py-4 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="p-3">
                                        <span class="text-sm text-zinc-200">{{ item._pro_name || item.mvs_pro_code }}</span>
                                    </td>
                                    <td class="p-3">
                                        <span class="text-sm text-zinc-400">{{ item._war_name || getWarehouseName(item.mvs_war_id) }}</span>
                                    </td>
                                    <td class="p-3 text-center text-xs font-bold text-indigo-400 bg-indigo-500/10 rounded-md">
                                        {{ getProductIvaLabel(item.mvs_pro_code) }}
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-1">
                                            <input type="number" v-model="item.mvs_quantity" @input="calculateTotals"
                                                :max="item._available_qty"
                                                :step="getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                                :min="getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                                class="w-20 text-center border-none bg-zinc-950/50 rounded-md py-1.5 focus:ring-1 focus:ring-indigo-600 text-zinc-300" />
                                            <span v-if="item.mvs_pro_code" class="text-[10px] font-bold uppercase text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded">
                                                {{ getUndLabel(getProductUnd(item.mvs_pro_code)) }}
                                            </span>
                                        </div>
                                        <div class="text-[9px] text-zinc-500 text-center mt-0.5">Máx: {{ item._available_qty }}</div>
                                    </td>
                                    <td class="p-3">
                                        <span class="block w-full text-right font-bold text-zinc-200 py-1.5">
                                            {{ Number(item.mvs_unit_price).toFixed(4) }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-right font-mono text-indigo-400 font-medium text-xs py-1">
                                            {{ item.mvs_unit_price_ref }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-right font-bold text-zinc-400">
                                        {{ getItemTotal(item) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
input[type=number] {
  -moz-appearance: textfield;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
</style>
