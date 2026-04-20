<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Landmark, Calculator, Loader2, Undo2, Search } from 'lucide-vue-next';
import Input from '@/components/ui/input/Input.vue';
import SelectorModal from '@/components/SelectorModal.vue';

const props = defineProps<{
    suppliers: any[];
    products: any[];
    warehouses: any[];
    rates: any[];
    coins: string[];
    latest_rate: any;
    ivas: string[];
}>();

const form = useForm({
    invoice_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    invoice_supplier_id: '',
    invoice_valorcam: props.latest_rate ? Number(props.latest_rate.rate_usd) : 1,
    invoice_coin: 'USD',
    
    items: [
        { mov_pro_code: '', mov_war_id: '', mov_quantity: 1, mov_quantity_max: 999999, mov_unit_cost_ref: 0, mov_unit_cost: 0, mov_pro_iva: 0 }
    ],
    invoice_subtotal: 0,
    invoice_iva: 0,
    invoice_exento: 0,
    invoice_total: 0,
    invoice_reference: ''
});

const unpaidInvoices = ref<any[]>([]);
const selectedInvoiceId = ref('');
const isLoadingInvoices = ref(false);
const isLoadingItems = ref(false);

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
    if (!invoiceId) return;
    
    isLoadingItems.value = true;
    try {
        const response = await fetch(`/api/purchase-orders/${invoiceId}/items`);
        const items = await response.json();
        
        const invoice = unpaidInvoices.value.find(i => i.id == invoiceId);
        if (invoice) {
            form.invoice_coin = invoice.invoice_coin;
            form.invoice_valorcam = Number(invoice.invoice_valorcam);
        }

        // Limpiar y asignar nuevos items
        form.items = []; // Asegurar limpieza total
        form.items = items.map((item: any) => ({
            mov_pro_code: item.mov_pro_code,
            mov_war_id: item.mov_war_id,
            mov_quantity: Number(item.mov_quantity_remaining),
            mov_quantity_max: Number(item.mov_quantity_remaining),
            mov_unit_cost: Number(item.mov_unit_cost),
            mov_unit_cost_ref: Number(item.mov_unit_cost_ref),
            mov_pro_iva: Number(item.mov_pro_iva)
        }));
        
        calculateTotals();
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
    
    if (newVal) {
        fetchInvoiceItems(newVal);
    }
});

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) return null;
    const exactRate = props.rates.find(r => r.rate_date === date);
    if (exactRate) return exactRate;
    const pastRates = props.rates
        .filter(r => r.rate_date < date)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));
    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const updateExchangeRate = () => {
    const today = new Date().toISOString().split('T')[0];

    if (form.invoice_date === today && props.latest_rate) {
        if (form.invoice_coin === 'USD') {
            form.invoice_valorcam = 1;
        } else {
            form.invoice_valorcam = form.invoice_coin === 'COP'
                ? Number(props.latest_rate.rate_cop)
                : Number(props.latest_rate.rate_usd);
        }
    } else {
        const selectedRate = getBestRateForDate(form.invoice_date);
        if (selectedRate) {
            if (form.invoice_coin === 'USD') {
                form.invoice_valorcam = 1;
            } else if (form.invoice_coin === 'COP') {
                form.invoice_valorcam = Number(selectedRate.rate_cop);
            } else if (form.invoice_coin === 'VES' || form.invoice_coin === 'BS') {
                form.invoice_valorcam = Number(selectedRate.rate_usd);
            } else if (form.invoice_coin === 'EUR') {
                form.invoice_valorcam = Number(selectedRate.rate_eur);
            }
        }
    }

    form.items.forEach((_, i) => updateCostRef(i));
};

const updateCostRef = (index: number) => {
    const item = form.items[index];
    const tasaManual = Number(form.invoice_valorcam) || 1;
    const costoUnitario = Number(item.mov_unit_cost) || 0;

    let totalEnUsd = 0;

    if (form.invoice_coin === 'USD') {
        totalEnUsd = costoUnitario;
    } else {
        totalEnUsd = costoUnitario / tasaManual;
    }

    item.mov_unit_cost_ref = Number(totalEnUsd.toFixed(4));
    calculateTotals();
};

const getProductIvaRate = (proCode: string) => {
    if (!proCode) return 0;
    const product = props.products.find(p => p.pro_code === proCode);
    return product && product.pro_iva ? Number(product.pro_iva) / 100 : 0;
};

const getProductIvaLabel = (proCode: string) => {
    if (!proCode) return '-';
    const product = props.products.find(p => p.pro_code === proCode);
    if (!product || !product.pro_iva) return '-';
    return product.pro_iva === '0' ? 'EX' : `${product.pro_iva}%`;
};

const calculateTotals = () => {
    let subtotal = 0;
    let sumIva = 0;
    let exento = 0;

    form.items.forEach(item => {
        const lineTotal = Number(item.mov_quantity) * Number(item.mov_unit_cost);
        subtotal += lineTotal;
        const ivaPercentage = getProductIvaRate(item.mov_pro_code) * 100;
        const ivaRate = ivaPercentage / 100;

        sumIva += lineTotal * ivaRate;
        item.mov_pro_iva = ivaPercentage;

        if (ivaRate === 0) {
            exento += lineTotal;
        }
    });

    form.invoice_subtotal = Number(subtotal.toFixed(4));
    form.invoice_iva = Number(sumIva.toFixed(4));
    form.invoice_exento = Number(exento.toFixed(4));
    form.invoice_total = Number((subtotal + sumIva).toFixed(4));
};

const getItemTotal = (item: any) => {
    const baseTotal = Number(item.mov_quantity) * Number(item.mov_unit_cost);
    const ivaRate = getProductIvaRate(item.mov_pro_code);
    return (baseTotal + (baseTotal * ivaRate)).toFixed(2);
};

const addItem = () => {
    form.items.push({ mov_pro_code: '', mov_war_id: '', mov_quantity: 1, mov_quantity_max: 999999, mov_unit_cost_ref: 0, mov_unit_cost: 0, mov_pro_iva: 0 });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        calculateTotals();
    }
};

const getSupplierName = (id: string | number) => {
    const s = props.suppliers.find(s => s.id == id);
    return s ? (s.supplier_name || s.name) : 'Seleccione proveedor';
};

const getProductName = (code: string) => {
    const p = props.products.find(p => p.pro_code === code);
    return p ? p.pro_name : 'Seleccione producto';
};

const selectSupplier = (supplier: any) => {
    form.invoice_supplier_id = supplier.id;
};

const selectProduct = (index: number, product: any) => {
    form.items[index].mov_pro_code = product.pro_code;
    calculateTotals();
};

const submit = () => {
    // Filtrar items que no tengan producto o almacén antes de enviar
    form.items = form.items.filter(item => item.mov_pro_code && item.mov_war_id);
    
    if (form.items.length === 0) {
        alert('Debe agregar al menos un producto válido');
        return;
    }
    
    const invalidItem = form.items.find((item: any) => item.mov_quantity > item.mov_quantity_max);
    if (invalidItem) {
        alert('La cantidad a devolver de un producto supera lo disponible en la factura.');
        return;
    }

    form.post('/purchase-returns', {
        preserveScroll: true,
    });
};

onMounted(() => updateExchangeRate());
</script>

<template>
    <Head title="Nueva Devolución de Compra" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/purchase-returns" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold flex items-center gap-2">
                            <Undo2 class="w-5 h-5 text-orange-400" />
                            Nueva Devolución de Compra
                        </h1>
                        <p class="text-xs text-zinc-500">Salida de mercancía por devolución a proveedor</p>
                    </div>
                </div>
                <button
                    @click="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center px-5 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-lg font-semibold transition-all disabled:opacity-50 shadow-lg shadow-orange-900/20"
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
                        <div class="flex items-center gap-2 text-orange-400 font-bold text-xs uppercase tracking-widest">
                            <Landmark class="w-4 h-4" /> Configuración General
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">N° Documento</label>
                            <Input type="text" v-model="form.invoice_number" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 sm:text-sm transition-colors" />
                            <p v-if="form.errors.invoice_number" class="text-xs text-red-500 mt-1">{{ form.errors.invoice_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Proveedor</label>
                            <SelectorModal
                                title="Seleccionar Proveedor"
                                description="Busque y seleccione un proveedor de la lista"
                                :items="suppliers"
                                :searchFields="['supplier_name', 'name', 'supplier_rif', 'rif']"
                                :displayFields="[
                                    { label: 'Nombre', key: 'supplier_name' },
                                    { label: 'RIF', key: 'supplier_rif' }
                                ]"
                                :triggerLabel="getSupplierName(form.invoice_supplier_id)"
                                :selectedValue="form.invoice_supplier_id"
                                @select="selectSupplier"
                            />
                            <p v-if="form.errors.invoice_supplier_id" class="text-xs text-red-500 mt-1">{{ form.errors.invoice_supplier_id }}</p>
                        </div>

                        <div v-if="form.invoice_supplier_id">
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5 flex items-center gap-2">
                                Factura Original
                                <Loader2 v-if="isLoadingInvoices" class="w-3 h-3 animate-spin text-orange-400" />
                            </label>
                            <select v-model="selectedInvoiceId" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-orange-600 focus:border-orange-600 sm:text-sm transition-colors">
                                <option value="">Seleccione factura para devolución</option>
                                <option v-for="inv in unpaidInvoices" :key="inv.id" :value="inv.id">
                                    #{{ inv.invoice_number }} - {{ inv.invoice_date }} (${{ inv.invoice_total }})
                                </option>
                            </select>
                            <p v-if="unpaidInvoices.length === 0 && !isLoadingInvoices" class="text-[10px] text-zinc-500 mt-1">No hay facturas pendientes para este proveedor</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Fecha</label>
                                <input type="date" v-model="form.invoice_date" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-orange-600" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Moneda</label>
                                <select v-model="form.invoice_coin" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-orange-600">
                                    <option v-for="c in coins" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-4 bg-orange-900/10 rounded-lg border border-orange-500/20">
                            <label class="block text-xs font-bold text-orange-400 uppercase mb-1.5">Tasa de Cambio Manual</label>
                            <input
                                type="number"
                                step="0.0001"
                                v-model="form.invoice_valorcam"
                                @input="form.items.forEach((_, i) => updateCostRef(i))"
                                class="w-full bg-zinc-950 border-zinc-700 rounded-md text-sm font-mono font-bold text-orange-300 focus:ring-orange-600"
                            />
                        </div>

                        
                    </div>

                    <div class="bg-orange-600 text-white rounded-xl p-6 shadow-xl relative overflow-hidden">
                        <Calculator class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10" />
                        <div class="relative z-10 space-y-3">
                            <div class="flex justify-between text-xs text-orange-100 font-medium"><span>SUBTOTAL</span> <span>{{ form.invoice_subtotal }}</span></div>
                            <div class="flex justify-between text-xs text-orange-100 font-medium"><span>EXENTO</span> <span>{{ form.invoice_exento }}</span></div>
                            <div class="flex justify-between text-xs text-orange-100 font-medium"><span>IVA</span> <span>{{ form.invoice_iva }}</span></div>
                            <div class="pt-3 border-t border-white/10 flex justify-between items-end">
                                <span class="text-white font-bold text-sm uppercase">Total {{ form.invoice_coin }}</span>
                                <span class="text-3xl font-black tabular-nums">{{ form.invoice_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                        <span class="text-xs font-bold uppercase text-zinc-500 tracking-wider">Detalle de Productos</span>
                        <button @click="addItem" class="text-xs font-bold text-orange-400 hover:text-orange-300 flex items-center gap-1.5 px-3 py-1.5 hover:bg-orange-500/10 rounded-lg transition-colors">
                            <PlusCircle class="w-4 h-4" /> Agregar Item
                        </button>
                    </div>

                    <div class="overflow-x-auto relative">
                        <div v-if="isLoadingItems" class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm z-10 flex items-center justify-center">
                            <div class="flex flex-col items-center gap-2">
                                <Loader2 class="w-8 h-8 animate-spin text-orange-500" />
                                <span class="text-xs font-medium text-zinc-400">Cargando productos...</span>
                            </div>
                        </div>
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                                <tr>
                                    <th class="px-4 py-4 text-left">Producto</th>
                                    <th class="px-4 py-4 text-left">Almacén</th>
                                    <th class="px-4 py-4 text-center text-orange-400">IVA</th>
                                    <th class="px-4 py-4 text-center w-24">Cant.</th>
                                    <th class="px-4 py-4 text-right">Costo Unit.</th>
                                    <th class="px-4 py-4 text-right text-orange-400">Ref ($)</th>
                                    <th class="px-4 py-4 text-right">Total</th>
                                    <th class="px-4 py-4 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="p-3 min-w-[250px]">
                                        <SelectorModal
                                            title="Seleccionar Producto"
                                            :items="products"
                                            :searchFields="['pro_name', 'pro_code']"
                                            :displayFields="[
                                                { label: 'Código', key: 'pro_code', class: 'font-mono text-xs' },
                                                { label: 'Nombre', key: 'pro_name' }
                                            ]"
                                            :triggerLabel="getProductName(item.mov_pro_code)"
                                            :selectedValue="item.mov_pro_code"
                                            triggerClass="bg-transparent border-none hover:bg-zinc-800/50"
                                            @select="(p) => selectProduct(index, p)"
                                        />
                                        <p v-if="form.errors[`items.${index}.mov_pro_code`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_pro_code`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <select v-model="item.mov_war_id" class="w-full bg-transparent border-none focus:ring-0 text-sm text-zinc-300">
                                            <option value="" class="bg-zinc-900">Seleccione...</option>
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id" class="bg-zinc-900">{{ w.war_name }}</option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.mov_war_id`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_war_id`] }}</p>
                                    </td>
                                    <td class="p-3 text-center text-xs font-bold text-orange-400 bg-orange-500/10 rounded-md">
                                        {{ getProductIvaLabel(item.mov_pro_code) }}
                                    </td>
                                    <td class="p-3">
                                        <input type="number" v-model="item.mov_quantity" :max="item.mov_quantity_max" min="0.01" step="0.01" @input="calculateTotals" class="w-full text-center border-none bg-zinc-950/50 rounded-md py-1.5 focus:ring-1 focus:ring-orange-600 text-zinc-300" />
                                        <p v-if="form.errors[`items.${index}.mov_quantity`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_quantity`] }}</p>
                                        <p v-if="item.mov_quantity > item.mov_quantity_max" class="text-[10px] text-red-500 mt-1">Disp. max: {{ item.mov_quantity_max }}</p>
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.0001" v-model="item.mov_unit_cost" @input="updateCostRef(index)" class="w-full text-right border-none bg-transparent font-bold text-zinc-200 focus:ring-0" />
                                        <p v-if="form.errors[`items.${index}.mov_unit_cost`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_unit_cost`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-right font-mono text-orange-400 font-medium text-xs py-1">
                                            {{ item.mov_unit_cost_ref }}
                                        </div>
                                    </td>
                                    <td class="p-3 text-right font-bold text-zinc-400">
                                        {{ getItemTotal(item) }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <button @click="removeItem(index)" class="text-zinc-600 hover:text-red-500 transition-colors p-1" title="Eliminar fila">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
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
  -moz-appearance: none;
  appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
</style>
