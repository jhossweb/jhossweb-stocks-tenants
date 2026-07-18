<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    FileDown, ArrowLeft, CalendarDays, Warehouse,
    AlertCircle, Hash, Ruler, Loader2
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/composables/useDate';

interface Warehouse {
    id: number;
    war_name: string;
}

interface CurrentStock {
    stk_war_id: number;
    stk_cant: number;
    war_name: string;
}

interface Movement {
    fecha: string;
    documento: string;
    tipo_transaccion: string;
    factor: number;
    cantidad: number;
    contraparte: string;
    entrada: number;
    salida: number;
    precio_unitario: number;
    precio_unitario_ref: number;
    total_documento: number;
    moneda: string | null;
    origen: string;
    saldo: number;
}

const props = defineProps<{
    product: {
        pro_code: string;
        pro_name: string;
        pro_und: string;
        pro_conversion_factor: number;
        pro_cant_min: number;
        pro_type: string;
    };
    warehouses: Warehouse[];
    currentStocks: CurrentStock[];
}>();

const selectedWarehouse = ref('');
const selectedYear = ref('');
const selectedMonth = ref('');

const years = ref<number[]>([]);
const months = ref<number[]>([]);
const movements = ref<Movement[]>([]);
const initialBalance = ref(0);

const loadingYears = ref(false);
const loadingMonths = ref(false);
const loadingMovements = ref(false);
const loaded = ref(false);

const monthNames: Record<number, string> = {
    1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril',
    5: 'Mayo', 6: 'Junio', 7: 'Julio', 8: 'Agosto',
    9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre',
};

const undLabels: Record<string, string> = {
    und: 'Und',
    mts: 'Mts',
    kg: 'Kg',
    m2: 'm²',
};

const totalEntradas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.entrada, 0)
);

const totalSalidas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.salida, 0)
);

const stockActual = computed(() => {
    if (!selectedWarehouse.value) {
return 0;
}

    const stock = props.currentStocks.find(
        s => s.stk_war_id === Number(selectedWarehouse.value)
    );

    return stock?.stk_cant ?? 0;
});

const apiBase = `/api/kardex/${props.product.pro_code}`;

watch(selectedWarehouse, async (val) => {
    selectedYear.value = '';
    selectedMonth.value = '';
    years.value = [];
    months.value = [];
    movements.value = [];
    loaded.value = false;

    if (!val) {
return;
}

    loadingYears.value = true;

    try {
        const res = await fetch(`${apiBase}/years?warehouse_id=${val}`);

        if (!res.ok) {
throw new Error(`HTTP ${res.status}`);
}

        years.value = await res.json();
    } catch (e) {
        console.error('Error fetching years:', e);
    } finally {
        loadingYears.value = false;
    }
});

watch(selectedYear, async (val) => {
    selectedMonth.value = '';
    months.value = [];
    movements.value = [];
    loaded.value = false;

    if (!val || !selectedWarehouse.value) {
return;
}

    loadingMonths.value = true;

    try {
        const res = await fetch(
            `${apiBase}/months?warehouse_id=${selectedWarehouse.value}&year=${val}`
        );

        if (!res.ok) {
throw new Error(`HTTP ${res.status}`);
}

        months.value = await res.json();
    } catch (e) {
        console.error('Error fetching months:', e);
    } finally {
        loadingMonths.value = false;
    }
});

watch([selectedWarehouse, selectedYear, selectedMonth], async () => {
    if (!selectedWarehouse.value || !selectedYear.value || !selectedMonth.value) {
        movements.value = [];
        loaded.value = false;

        return;
    }

    loadMovements();
});

async function loadMovements() {
    loadingMovements.value = true;
    loaded.value = false;

    try {
        const res = await fetch(
            `${apiBase}/movements?warehouse_id=${selectedWarehouse.value}&year=${selectedYear.value}&month=${selectedMonth.value}`
        );

        if (!res.ok) {
throw new Error(`HTTP ${res.status}`);
}

        const data = await res.json();
        movements.value = data.movements;
        initialBalance.value = data.initial_balance;
        loaded.value = true;
    } catch (e) {
        console.error('Error fetching movements:', e);
    } finally {
        loadingMovements.value = false;
    }
}

function formatNumber(n: number, decimals = 2): string {
    return Number(n).toLocaleString('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function pdfUrl(): string {
    return `${apiBase}/pdf?warehouse_id=${selectedWarehouse.value}&year=${selectedYear.value}&month=${selectedMonth.value}`;
}
</script>

<template>
    <Head :title="`Kardex - ${product.pro_name}`" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/products" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Kardex</h1>
                        <p class="text-xs text-zinc-500">Historial de movimientos del producto</p>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <h2 class="text-xl font-bold text-zinc-100">
                            {{ product.pro_name }}
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1 text-xs font-mono text-zinc-400 bg-zinc-800 px-2.5 py-1 rounded-md">
                                <Hash class="h-3.5 w-3.5" />
                                {{ product.pro_code }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-zinc-400 bg-zinc-800 px-2.5 py-1 rounded-md">
                                <Ruler class="h-3.5 w-3.5" />
                                {{ undLabels[product.pro_und] || product.pro_und }}
                            </span>
                            <span v-if="product.pro_conversion_factor > 1" class="inline-flex items-center gap-1 text-xs text-amber-400 bg-amber-900/30 px-2.5 py-1 rounded-md">
                                Factor: ×{{ product.pro_conversion_factor }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-400 flex items-center gap-2">
                            <Warehouse class="h-4 w-4" />
                            Almacén
                        </label>
                        <Select v-model="selectedWarehouse">
                            <SelectTrigger class="bg-zinc-800 border-zinc-700 text-zinc-100">
                                <SelectValue placeholder="Selecciona almacén" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                    {{ w.war_name }}
                                    <span v-if="product.pro_type !== 'compuesto'" class="text-xs text-zinc-500 ml-2">
                                        (Stock: {{ formatNumber(currentStocks.find(s => s.stk_war_id === w.id)?.stk_cant ?? 0) }})
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-400 flex items-center gap-2">
                            <CalendarDays class="h-4 w-4" />
                            Año
                        </label>
                        <Select v-model="selectedYear" :disabled="!selectedWarehouse || loadingYears">
                            <SelectTrigger class="bg-zinc-800 border-zinc-700 text-zinc-100">
                                <SelectValue :placeholder="loadingYears ? 'Cargando...' : 'Selecciona año'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="y in years" :key="y" :value="String(y)">
                                    {{ y }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-400 flex items-center gap-2">
                            <CalendarDays class="h-4 w-4" />
                            Mes
                        </label>
                        <Select v-model="selectedMonth" :disabled="!selectedYear || loadingMonths">
                            <SelectTrigger class="bg-zinc-800 border-zinc-700 text-zinc-100">
                                <SelectValue :placeholder="loadingMonths ? 'Cargando...' : 'Selecciona mes'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="m in months" :key="m" :value="String(m)">
                                    {{ monthNames[m] || m }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div v-if="loaded" class="mt-4 pt-4 border-t border-zinc-800 flex justify-end">
                    <a :href="pdfUrl()" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-zinc-700 rounded-lg hover:bg-zinc-800 transition-colors text-zinc-300">
                        <FileDown class="h-4 w-4" />
                        Exportar PDF
                    </a>
                </div>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div v-if="loaded" class="grid grid-cols-2 md:grid-cols-4 gap-px bg-zinc-800">
                    <div class="bg-zinc-900 p-4 text-center">
                        <p class="text-[10px] text-zinc-500 uppercase tracking-wide font-semibold">Saldo Inicial</p>
                        <p class="text-lg font-bold text-zinc-100 mt-1">
                            {{ formatNumber(initialBalance) }} <span class="text-xs font-normal text-zinc-500">{{ undLabels[product.pro_und] }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p class="text-[10px] text-zinc-500 uppercase tracking-wide font-semibold">Total Entradas</p>
                        <p class="text-lg font-bold text-green-400 mt-1">
                            +{{ formatNumber(totalEntradas) }} <span class="text-xs font-normal text-zinc-500">{{ undLabels[product.pro_und] }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p class="text-[10px] text-zinc-500 uppercase tracking-wide font-semibold">Total Salidas</p>
                        <p class="text-lg font-bold text-red-400 mt-1">
                            -{{ formatNumber(totalSalidas) }} <span class="text-xs font-normal text-zinc-500">{{ undLabels[product.pro_und] }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p class="text-[10px] text-zinc-500 uppercase tracking-wide font-semibold">Stock Actual</p>
                        <p class="text-lg font-bold text-zinc-100 mt-1">
                            {{ formatNumber(stockActual) }} <span class="text-xs font-normal text-zinc-500">{{ undLabels[product.pro_und] }}</span>
                        </p>
                    </div>
                </div>

                <div v-if="loadingMovements" class="flex items-center justify-center py-20">
                    <Loader2 class="h-8 w-8 animate-spin text-indigo-400" />
                    <span class="ml-3 text-zinc-500">Cargando movimientos...</span>
                </div>

                <div v-if="!selectedWarehouse && !loadingMovements" class="flex flex-col items-center justify-center py-20 text-zinc-500">
                    <Warehouse class="h-12 w-12 mb-3 opacity-30" />
                    <p class="text-sm">Selecciona un almacén, año y mes para ver los movimientos</p>
                </div>

                <div v-if="loaded && movements.length === 0 && !loadingMovements" class="flex flex-col items-center justify-center py-20 text-zinc-500">
                    <AlertCircle class="h-12 w-12 mb-3 opacity-30" />
                    <p class="text-sm">No hay movimientos en este período</p>
                </div>

                <div v-if="loaded && movements.length > 0 && !loadingMovements" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Contraparte</th>
                                <th class="px-4 py-4 text-right">Entrada</th>
                                <th class="px-4 py-4 text-right">Salida</th>
                                <th class="px-4 py-4 text-right">Precio Unit.</th>
                                <th class="px-4 py-4 text-right">Total Doc.</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr class="bg-zinc-800/20 font-medium">
                                <td class="px-4 py-4 text-zinc-500 text-xs" colspan="8">
                                    Saldo inicial
                                </td>
                                <td class="px-4 py-4 text-right font-bold text-sm text-zinc-100">
                                    {{ formatNumber(initialBalance) }}
                                </td>
                            </tr>

                            <tr v-for="(mov, index) in movements" :key="index"
                                class="hover:bg-zinc-800/30 transition-colors"
                                :class="{
                                    'bg-blue-900/5': mov.origen === 'compra',
                                    'bg-red-900/5': mov.origen === 'venta',
                                    'bg-amber-900/5': mov.origen === 'ajuste'
                                }"
                            >
                                <td class="px-4 py-4 whitespace-nowrap font-mono text-xs text-zinc-300">
                                    {{ formatDate(mov.fecha) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap font-mono text-xs max-w-[140px] truncate text-zinc-300" :title="mov.documento">
                                    {{ mov.documento }}
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                        :class="{
                                            'bg-blue-900/30 text-blue-400': mov.origen === 'compra',
                                            'bg-red-900/30 text-red-400': mov.origen === 'venta',
                                            'bg-amber-900/30 text-amber-400': mov.origen === 'ajuste',
                                            'bg-zinc-800 text-zinc-400': !['compra', 'venta', 'ajuste'].includes(mov.origen),
                                        }">
                                        {{ mov.tipo_transaccion || mov.origen }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm max-w-[160px] truncate text-zinc-400" :title="mov.contraparte">
                                    {{ mov.contraparte }}
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap text-sm">
                                    <span v-if="mov.entrada > 0" class="text-green-400 font-medium">
                                        +{{ formatNumber(mov.entrada) }}
                                    </span>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap text-sm">
                                    <span v-if="mov.salida > 0" class="text-red-400 font-medium">
                                        -{{ formatNumber(mov.salida) }}
                                    </span>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap text-sm font-mono text-zinc-400">
                                    <template v-if="mov.precio_unitario > 0">
                                        {{ formatNumber(mov.precio_unitario) }}
                                    </template>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap text-sm font-mono text-zinc-300">
                                    <template v-if="mov.total_documento > 0">
                                        {{ formatNumber(mov.total_documento) }}
                                        <span v-if="mov.moneda" class="text-xs text-zinc-500 ml-0.5">{{ mov.moneda }}</span>
                                    </template>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap text-sm font-bold text-zinc-100">
                                    {{ formatNumber(mov.saldo) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
