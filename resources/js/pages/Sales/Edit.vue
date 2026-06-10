<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Landmark, Calculator, Loader2 } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';

const props = defineProps<{
    customers: any[];
    products: any[];
    warehouses: any[];
    rates: any[];
    coins: string[];
    latest_rate: any;
    transactionTypes: any[];
    ivas: string[];
    sale: any;
}>();

const form = useForm({
    sls_number: props.sale.sls_number || '',
    sls_date: props.sale.sls_date || new Date().toISOString().split('T')[0],
    sls_customer_id: props.sale.sls_customer_id || '',
    sls_valorcam: props.sale.sls_valorcam || (props.latest_rate ? Number(props.latest_rate.rate_usd) : 1),
    sls_coin: props.sale.sls_coin || 'USD',

    items: props.sale.movements && props.sale.movements.length > 0 ? props.sale.movements.map((m: any) => ({
        id: m.id,
        mvs_pro_code: m.mvs_pro_code,
        mvs_war_id: m.mvs_war_id,
        mvs_quantity: m.mvs_quantity,
        mvs_unit_price_ref: m.mvs_unit_price_ref,
        mvs_unit_price: m.mvs_unit_price,
        mvs_pro_iva: m.mvs_pro_iva,
    })) : [
        { mvs_pro_code: '', mvs_war_id: '', mvs_quantity: 1, mvs_unit_price_ref: 0, mvs_unit_price: 0, mvs_pro_iva: 0 }
    ],
    sls_subtotal: props.sale.sls_subtotal || 0,
    sls_iva: props.sale.sls_iva || 0,
    sls_exento: props.sale.sls_exento || 0,
    sls_total: props.sale.sls_total || 0,
});

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
return null;
}

    const exactRate = props.rates.find(r => r.rate_date === date);

    if (exactRate) {
return exactRate;
}

    const pastRates = props.rates
        .filter(r => r.rate_date < date)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0];
};

const updateExchangeRate = () => {
    const today = new Date().toISOString().split('T')[0];

    if (form.sls_date === today && props.latest_rate) {
        if (form.sls_coin === 'USD') {
            form.sls_valorcam = 1;
        } else {
            form.sls_valorcam = form.sls_coin === 'COP'
                ? Number(props.latest_rate.rate_cop)
                : Number(props.latest_rate.rate_usd);
        }
    } else {
        const selectedRate = getBestRateForDate(form.sls_date);

        if (selectedRate) {
            if (form.sls_coin === 'USD') {
                form.sls_valorcam = 1;
            } else if (form.sls_coin === 'COP') {
                form.sls_valorcam = Number(selectedRate.rate_cop);
            } else if (form.sls_coin === 'VES' || form.sls_coin === 'BS') {
                form.sls_valorcam = Number(selectedRate.rate_usd);
            } else if (form.sls_coin === 'EUR') {
                form.sls_valorcam = Number(selectedRate.rate_eur);
            }
        }
    }

    form.items.forEach((_, i) => updatePriceFromRef(i));
};

const updatePriceFromRef = (index: number) => {
    const item = form.items[index];
    const tasaManual = Number(form.sls_valorcam) || 1;
    const usdPrice = Number(item.mvs_unit_price_ref) || 0;

    if (form.sls_coin === 'USD') {
        item.mvs_unit_price = usdPrice;
    } else {
        item.mvs_unit_price = usdPrice * tasaManual;
    }

    calculateTotals();
};

const getProductIvaRate = (proCode: string) => {
    if (!proCode) {
return 0;
}

    const product = props.products.find(p => p.pro_code === proCode);

    return product && product.pro_iva ? Number(product.pro_iva) / 100 : 0;
};

const getProductIvaLabel = (proCode: string) => {
    if (!proCode) {
return '-';
}

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

    form.items.forEach(item => {
        const lineTotal = Number(item.mvs_quantity) * Number(item.mvs_unit_price);
        subtotal += lineTotal;
        const ivaPercentage = getProductIvaRate(item.mvs_pro_code) * 100;
        const ivaRate = ivaPercentage / 100;

        sumIva += lineTotal * ivaRate;
        item.mvs_pro_iva = ivaPercentage;

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

const addItem = () => {
    form.items.push({ mvs_pro_code: '', mvs_war_id: '', mvs_quantity: 1, mvs_unit_price_ref: 0, mvs_unit_price: 0, mvs_pro_iva: 0 });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        calculateTotals();
    }
};

const getProductName = (code: string) => {
    const p = props.products.find(p => p.pro_code === code);

    return p ? `${p.pro_name} - $${p.latest_price}` : 'Seleccione producto';
};

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

const selectProduct = (index: number, product: any) => {
    form.items[index].mvs_pro_code = product.pro_code;
    form.items[index].mvs_pro_iva = Number(product.pro_iva) || 0;

    const usdPrice = Number(product.latest_price) || 0;
    const tasaManual = Number(form.sls_valorcam) || 1;

    form.items[index].mvs_unit_price_ref = usdPrice;

    if (form.sls_coin === 'USD') {
        form.items[index].mvs_unit_price = usdPrice;
    } else {
        form.items[index].mvs_unit_price = usdPrice * tasaManual;
    }

    calculateTotals();
};

const submit = () => {
    form.put(`/sales/${props.sale.id}`, {
        preserveScroll: true,
        onSuccess: () => console.log('Éxito!'),
        onError: (errors) => console.log('Errores:', errors),
    });
};
</script>

<template>
    <Head title="Editar Venta" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/sales" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Editar Venta</h1>
                        <p class="text-xs text-zinc-500">Registro de facturas de venta e inventario</p>
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
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">N° Factura</label>
                            <Input type="text" v-model="form.sls_number" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                            <p v-if="form.errors.sls_number" class="text-xs text-red-500 mt-1">{{ form.errors.sls_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Cliente</label>
                            <select v-model="form.sls_customer_id" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors">
                                <option value="">Seleccione cliente</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.clt_name }}</option>
                            </select>
                            <p v-if="form.errors.sls_customer_id" class="text-xs text-red-500 mt-1">{{ form.errors.sls_customer_id }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Fecha</label>
                                <input type="date" v-model="form.sls_date" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Moneda</label>
                                <select v-model="form.sls_coin" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600">
                                    <option v-for="c in coins" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-900/10 rounded-lg border border-indigo-500/20">
                            <label class="block text-xs font-bold text-indigo-400 uppercase mb-1.5">Tasa de Cambio Manual</label>
                            <input
                                type="number"
                                step="0.0001"
                                v-model="form.sls_valorcam"
                                @input="form.items.forEach((_, i) => updatePriceFromRef(i))"
                                class="w-full bg-zinc-950 border-zinc-700 rounded-md text-sm font-mono font-bold text-indigo-300 focus:ring-indigo-600"
                            />
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
                        <span class="text-xs font-bold uppercase text-zinc-500 tracking-wider">Detalle de Productos</span>
                        <button @click="addItem" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5 px-3 py-1.5 hover:bg-indigo-500/10 rounded-lg transition-colors">
                            <PlusCircle class="w-4 h-4" /> Agregar Item
                        </button>
                    </div>

                    <div class="overflow-x-auto">
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
                                                { label: 'Nombre', key: 'pro_name' },
                                                { label: 'Und', key: 'pro_und', class: 'font-mono text-xs text-center' },
                                                { label: 'Precio $', key: 'latest_price', class: 'font-mono text-xs text-right' },
                                                { label: 'Stock', key: 'total_stock', class: 'font-mono text-xs text-right' }
                                            ]"
                                            :triggerLabel="getProductName(item.mvs_pro_code)"
                                            :selectedValue="item.mvs_pro_code"
                                            triggerClass="bg-transparent border-none hover:bg-zinc-800/50"
                                            @select="(p) => selectProduct(index, p)"
                                        />
                                        <p v-if="form.errors[`items.${index}.mvs_pro_code`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mvs_pro_code`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <select v-model="item.mvs_war_id" class="w-full bg-transparent border-none focus:ring-0 text-sm text-zinc-300">
                                            <option value="" class="bg-zinc-900">Seleccione...</option>
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id" class="bg-zinc-900">{{ w.war_name }}</option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.mvs_war_id`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mvs_war_id`] }}</p>
                                    </td>
                                    <td class="p-3 text-center text-xs font-bold text-indigo-400 bg-indigo-500/10 rounded-md">
                                        {{ getProductIvaLabel(item.mvs_pro_code) }}
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-1">
                                            <input type="number" v-model="item.mvs_quantity"
                                                :step="getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                                :min="getProductUnd(item.mvs_pro_code) === 'und' ? 1 : 0.01"
                                                @input="calculateTotals"
                                                class="w-20 text-center border-none bg-zinc-950/50 rounded-md py-1.5 focus:ring-1 focus:ring-indigo-600 text-zinc-300" />
                                            <span v-if="item.mvs_pro_code" class="text-[10px] font-bold uppercase text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded">
                                                {{ getUndLabel(getProductUnd(item.mvs_pro_code)) }}
                                            </span>
                                        </div>
                                        <p v-if="form.errors[`items.${index}.mvs_quantity`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mvs_quantity`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <span class="block w-full text-right font-bold text-zinc-200 py-1.5">
                                            {{ Number(item.mvs_unit_price).toFixed(4) }}
                                        </span>
                                        <p v-if="form.errors[`items.${index}.mvs_unit_price`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mvs_unit_price`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-right font-mono text-indigo-400 font-medium text-xs py-1">
                                            {{ item.mvs_unit_price_ref }}
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
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
</style>
