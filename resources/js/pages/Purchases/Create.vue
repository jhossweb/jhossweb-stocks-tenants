<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Landmark, Calculator, Loader2, Search } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import Input from '@/components/ui/input/Input.vue';

const props = defineProps<{
    suppliers: any[];
    products: any[];
    warehouses: any[];
    rates: any[]; // Se espera un array de objetos: { rate_date, rate_usd, rate_cop, rate_eur }
    coins: string[];
    latest_rate: any;
    transactionTypes: any[];
    ivas: string[];
}>();

const form = useForm({
    invoice_number: '',
    invoice_date: new Date().toISOString().split('T')[0],
    invoice_supplier_id: '',
    invoice_valorcam: props.latest_rate ? Number(props.latest_rate.rate_usd) : 1,
    invoice_coin: 'USD',
    
    items: [
        { mov_pro_code: '', mov_war_id: '', mov_quantity: 1, mov_unit_cost_ref: 0, mov_unit_cost: 0, mov_pro_iva: 0 }
    ],
    invoice_subtotal: 0,
    invoice_iva: 0,
    invoice_exento: 0,
    invoice_total: 0,
});

// --- LÓGICA DE TASAS ---

const getBestRateForDate = (date: string) => {
    if (!props.rates || props.rates.length === 0) {
return null;
}

    // 1. Intentar buscar la tasa de la fecha exacta
    const exactRate = props.rates.find(r => r.rate_date === date);
    
    if (exactRate) {
return exactRate;
}

    // 2. Si no existe, buscar la más reciente anterior a esa fecha
    const pastRates = props.rates
        .filter(r => r.rate_date < date)
        .sort((a, b) => b.rate_date.localeCompare(a.rate_date));

    return pastRates.length > 0 ? pastRates[0] : props.rates[0]; 
};
/*
const updateExchangeRate = () => {
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
    
    // Recalcular el Costo Ref ($) para todos los items basado en la nueva tasa
    form.items.forEach((_, i) => updateCostRef(i));
};
*/
const updateExchangeRate = () => {
    // Si la fecha es la de hoy, podrías forzar que use la latest_rate
    const today = new Date().toISOString().split('T')[0];
    
    if (form.invoice_date === today && props.latest_rate) {
        if (form.invoice_coin === 'USD') {
            form.invoice_valorcam = 1;
        } else {
            // Ajusta según la moneda seleccionada usando los datos de la última tasa
            form.invoice_valorcam = form.invoice_coin === 'COP' 
                ? Number(props.latest_rate.rate_cop) 
                : Number(props.latest_rate.rate_usd);
        }
    } else {
        // Si es una fecha pasada, buscar en el histórico (tu lógica actual)
        const selectedRate = getBestRateForDate(form.invoice_date);

        if (selectedRate) {
            if (form.invoice_coin === 'USD') {
                form.invoice_valorcam = 1;
            } else if (form.invoice_coin === 'COP') {
                form.invoice_valorcam = Number(selectedRate.rate_cop);
                alert(selectedRate.rate_cop)
            } else if (form.invoice_coin === 'VES' || form.invoice_coin === 'BS') {
                form.invoice_valorcam = Number(selectedRate.rate_usd);
                alert(selectedRate.rate_usd)
            } else if (form.invoice_coin === 'EUR') {
            form.invoice_valorcam = Number(selectedRate.rate_eur);
            alert(selectedRate.rate_eur)
        }
        }
    }

    // Forzamos el recalculo de costos ref para todos los items
    form.items.forEach((_, i) => updateCostRef(i));
};


/*
const updateCostRef = (index: number) => {
    const item = form.items[index];
    const tasaActualInput = Number(form.invoice_valorcam) || 1;
    
    // Obtenemos la tasa USD del sistema para la triangulación (si fuera necesario comparar)
    const selectedRate = getBestRateForDate(form.invoice_date);
    const tasaUsdSistema = Number(selectedRate?.rate_usd) || 1;

    let totalEnUsd = 0;

    if (form.invoice_coin === 'USD') {
        // Si la factura ya está en dólares, el costo ref es el mismo costo unitario
        totalEnUsd = Number(item.mov_unit_cost);
    } else if (form.invoice_coin === 'BS' || form.invoice_coin === 'VES') {
        // Dividimos el costo en Bolívares entre la tasa del input (USD/VES)
        // Ejemplo: 1000 BS / 36.50 tasa = 27.39 USD
        totalEnUsd = Number(item.mov_unit_cost) / tasaActualInput;
    } else if (form.invoice_coin === 'COP') {
        // Para COP, normalmente se triangula: (COP / Tasa COP) / Tasa USD
        // Pero si tu tasaActualInput ya representa cuántos COP es 1 USD, sería directo.
        // Siguiendo tu lógica anterior de triangulación:
        totalEnUsd = (Number(item.mov_unit_cost) / tasaActualInput) / tasaUsdSistema;
    }

    // Guardamos con 4 decimales para mayor precisión en inventarios
    item.cost_ref = Number(totalEnUsd.toFixed(4));
    
    calculateTotals();
};
*/


const updateCostRef = (index: number) => {
    const item = form.items[index];
    
    // La tasa que el usuario ve/escribe (3600 para COP, 44.71 para BS, etc.)
    const tasaManual = Number(form.invoice_valorcam) || 1;
    
    // El costo unitario en la moneda de la factura
    const costoUnitario = Number(item.mov_unit_cost) || 0;

    let totalEnUsd = 0;

    if (form.invoice_coin === 'USD') {
        // Si la factura es en USD, el costo ya es la referencia
        totalEnUsd = costoUnitario;
    } else {
        // Para CUALQUIER otra moneda (COP, BS, VES, EUR):
        // Costo / Tasa = Valor en USD
        // Ejemplo COP: 3600 / 3600 = 1 USD
        // Ejemplo BS: 44.71 / 44.71 = 1 USD
        totalEnUsd = costoUnitario / tasaManual;
    }

    // Guardamos con 4 decimales para precisión
    item.mov_unit_cost_ref = Number(totalEnUsd.toFixed(4));
    
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
    form.items.push({ mov_pro_code: '', mov_war_id: '', mov_quantity: 1, mov_unit_cost_ref: 0, mov_unit_cost: 0, mov_pro_iva: 0 });
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
    console.log('Enviando formulario...', form.data());
    form.post('/purchases', { 
        preserveScroll: true,
        onSuccess: () => console.log('Éxito!'),
        onError: (errors) => console.log('Errores:', errors),
    });
};

onMounted(() => updateExchangeRate());
</script>

<template>
    <Head title="Nueva Compra" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/purchases" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Nueva Compra</h1>
                        <p class="text-xs text-zinc-500">Registro de facturas e inventario</p>
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

            <!-- Mostrar errores generales o de validación que no se capturan abajo -->
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
                            <Input type="text" v-model="form.invoice_number" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Fecha</label>
                                <Input type="date" v-model="form.invoice_date" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-1.5">Moneda</label>
                                <select v-model="form.invoice_coin" @change="updateExchangeRate" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600">
                                    <option v-for="c in coins" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-900/10 rounded-lg border border-indigo-500/20">
                            <label class="block text-xs font-bold text-indigo-400 uppercase mb-1.5">Tasa de Cambio Manual</label>
                            <Input 
                                type="number" 
                                step="0.0001" 
                                v-model="form.invoice_valorcam" 
                                @input="form.items.forEach((_, i) => updateCostRef(i))"
                                class="w-full bg-zinc-950 border-zinc-700 rounded-md text-sm font-mono font-bold text-indigo-300 focus:ring-indigo-600"
                            />
                        </div>

                       
                    </div>

                    <div class="bg-indigo-600 text-white rounded-xl p-6 shadow-xl relative overflow-hidden">
                        <Calculator class="absolute -right-4 -bottom-4 w-24 h-24 text-white/10" />
                        <div class="relative z-10 space-y-3">
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>SUBTOTAL</span> <span>{{ form.invoice_subtotal }}</span></div>
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>EXENTO</span> <span>{{ form.invoice_exento }}</span></div>
                            <div class="flex justify-between text-xs text-indigo-100 font-medium"><span>IVA</span> <span>{{ form.invoice_iva }}</span></div>
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
                                    <th class="px-4 py-4 text-right">Costo Unit.</th>
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
                                            <option v-for="w in warehouses" :key="w.id" :value="w.id" class="bg-zinc-900">{{ w.id }}</option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.mov_war_id`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_war_id`] }}</p>
                                    </td>
                                    <td class="p-3 text-center text-xs font-bold text-indigo-400 bg-indigo-500/10 rounded-md">
                                        {{ getProductIvaLabel(item.mov_pro_code) }}
                                    </td>
                                    <td class="p-3">
                                        <input type="number" v-model="item.mov_quantity" @input="calculateTotals" class="w-full text-center border-none bg-zinc-950/50 rounded-md py-1.5 focus:ring-1 focus:ring-indigo-600 text-zinc-300" />
                                        <p v-if="form.errors[`items.${index}.mov_quantity`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_quantity`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.0001" v-model="item.mov_unit_cost" @input="updateCostRef(index)" class="w-full text-right border-none bg-transparent font-bold text-zinc-200 focus:ring-0" />
                                        <p v-if="form.errors[`items.${index}.mov_unit_cost`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.mov_unit_cost`] }}</p>
                                    </td>
                                    <td class="p-3">
                                        <div class="text-right font-mono text-indigo-400 font-medium text-xs py-1">
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
/* Eliminar flechas de inputs numéricos */
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

/* Estilo para el calendario en dark mode */
input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
</style>