<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    PackageSearch, FileDown, ArrowLeft, CalendarDays, Warehouse,
    ArrowUpFromLine, ArrowDownFromLine, ChevronDown, Loader2,
    ScrollText, Box, AlertCircle, Hash, Ruler
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/composables/useDate';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '#' },
            { title: 'Productos', href: '/products' },
            { title: 'Kardex', href: '' },
        ],
    },
});

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
    und: 'Unidad',
    mts: 'Metros',
    kg: 'Kilogramos',
    m2: 'm²',
};

const totalEntradas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.entrada, 0)
);

const totalSalidas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.salida, 0)
);

const stockActual = computed(() => {
    if (!selectedWarehouse.value) return 0;
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
    if (!val) return;
    loadingYears.value = true;
    try {
        const res = await fetch(`${apiBase}/years?warehouse_id=${val}`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
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
    if (!val || !selectedWarehouse.value) return;
    loadingMonths.value = true;
    try {
        const res = await fetch(
            `${apiBase}/months?warehouse_id=${selectedWarehouse.value}&year=${val}`
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
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
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
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

function getOrigenColor(origen: string): string {
    const colors: Record<string, string> = {
        compra: 'text-blue-600 bg-blue-50 border-blue-200',
        venta: 'text-red-600 bg-red-50 border-red-200',
        ajuste: 'text-amber-600 bg-amber-50 border-amber-200',
    };
    return colors[origen] || 'text-gray-600 bg-gray-50 border-gray-200';
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

function getSaldoInicialMovementsCount(): number {
    return movements.value.length + 1;
}
</script>

<template>
    <Head :title="`Kardex - ${product.pro_name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <!-- Encabezado -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link href="/products">
                    <Button variant="ghost" size="icon" class="h-9 w-9">
                        <ArrowLeft class="h-5 w-5" />
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <ScrollText class="h-7 w-7 text-primary" />
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            Kardex
                        </h1>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Historial de movimientos del producto
                    </p>
                </div>
            </div>
        </div>

        <!-- Información del Producto -->
        <div class="bg-white dark:bg-gray-900 border border-sidebar-border rounded-2xl p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ product.pro_name }}
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 text-sm font-mono text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">
                            <Hash class="h-3.5 w-3.5" />
                            {{ product.pro_code }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-sm text-gray-500 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 rounded-full">
                            <Ruler class="h-3.5 w-3.5" />
                            {{ undLabels[product.pro_und] || product.pro_und }}
                        </span>
                        <span v-if="product.pro_conversion_factor > 1" class="inline-flex items-center gap-1 text-sm text-amber-600 bg-amber-50 dark:bg-amber-900/20 px-2.5 py-0.5 rounded-full border border-amber-200">
                            Factor: ×{{ product.pro_conversion_factor }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-gray-900 border border-sidebar-border rounded-2xl p-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Almacén -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <Warehouse class="h-4 w-4" />
                        Almacén
                    </label>
                    <Select v-model="selectedWarehouse">
                        <SelectTrigger>
                            <SelectValue placeholder="Selecciona almacén" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">
                                {{ w.war_name }}
                                <span v-if="product.pro_type !== 'compuesto'" class="text-xs text-gray-400 ml-2">
                                    (Stock: {{ formatNumber(currentStocks.find(s => s.stk_war_id === w.id)?.stk_cant ?? 0) }})
                                </span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Año -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <CalendarDays class="h-4 w-4" />
                        Año
                    </label>
                    <Select v-model="selectedYear" :disabled="!selectedWarehouse || loadingYears">
                        <SelectTrigger>
                            <SelectValue :placeholder="loadingYears ? 'Cargando...' : 'Selecciona año'" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="y in years" :key="y" :value="String(y)">
                                {{ y }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Mes -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <CalendarDays class="h-4 w-4" />
                        Mes
                    </label>
                    <Select v-model="selectedMonth" :disabled="!selectedYear || loadingMonths">
                        <SelectTrigger>
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

            <!-- Botón Exportar PDF -->
            <div v-if="loaded" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <a :href="pdfUrl()" target="_blank">
                    <Button variant="outline" class="gap-2">
                        <FileDown class="h-4 w-4" />
                        Exportar PDF
                    </Button>
                </a>
            </div>
        </div>

        <!-- Tabla de Movimientos -->
        <div class="bg-white dark:bg-gray-900 border border-sidebar-border rounded-2xl shadow-sm overflow-hidden">
            <!-- Resumen -->
            <div v-if="loaded" class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 border-b border-sidebar-border">
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Saldo Inicial</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(initialBalance) }} {{ undLabels[product.pro_und] }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Entradas</p>
                    <p class="text-lg font-bold text-green-600">
                        +{{ formatNumber(totalEntradas) }} {{ undLabels[product.pro_und] }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Total Salidas</p>
                    <p class="text-lg font-bold text-red-600">
                        -{{ formatNumber(totalSalidas) }} {{ undLabels[product.pro_und] }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Stock Actual</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ formatNumber(stockActual) }} {{ undLabels[product.pro_und] }}
                    </p>
                </div>
            </div>

            <!-- Cargando -->
            <div v-if="loadingMovements" class="flex items-center justify-center py-20">
                <Loader2 class="h-8 w-8 animate-spin text-primary" />
                <span class="ml-3 text-gray-500">Cargando movimientos...</span>
            </div>

            <!-- Sin selección -->
            <div v-if="!selectedWarehouse && !loadingMovements" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <Warehouse class="h-12 w-12 mb-3 opacity-30" />
                <p class="text-sm">Selecciona un almacén, año y mes para ver los movimientos</p>
            </div>

            <!-- Sin datos -->
            <div v-if="loaded && movements.length === 0 && !loadingMovements" class="flex flex-col items-center justify-center py-20 text-gray-400">
                <AlertCircle class="h-12 w-12 mb-3 opacity-30" />
                <p class="text-sm">No hay movimientos en este período</p>
            </div>

            <!-- Tabla -->
            <div v-if="loaded && movements.length > 0 && !loadingMovements" class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-sidebar-border bg-gray-50 dark:bg-gray-800/50">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Fecha</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Documento</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Tipo</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Contraparte</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Entrada</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Salida</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Precio Unit.</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Total Doc.</th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Fila de saldo inicial -->
                        <tr class="border-b border-sidebar-border bg-gray-50 dark:bg-gray-800/30 font-medium">
                            <td class="px-4 py-3 text-gray-500 text-xs" colspan="8">
                                Saldo inicial
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-sm">
                                {{ formatNumber(initialBalance) }}
                            </td>
                        </tr>

                        <tr v-for="(mov, index) in movements" :key="index"
                            class="border-b border-sidebar-border hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                            :class="{ 'bg-blue-50/30 dark:bg-blue-900/10': mov.origen === 'compra', 'bg-red-50/30 dark:bg-red-900/10': mov.origen === 'venta', 'bg-amber-50/30 dark:bg-amber-900/10': mov.origen === 'ajuste' }"
                        >
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-sm">
                                {{ formatDate(mov.fecha) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-mono text-sm max-w-[140px] truncate" :title="mov.documento">
                                {{ mov.documento }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border"
                                    :class="getOrigenColor(mov.origen)">
                                    {{ mov.tipo_transaccion || mov.origen }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm max-w-[160px] truncate" :title="mov.contraparte">
                                {{ mov.contraparte }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm">
                                <span v-if="mov.entrada > 0" class="text-green-600 font-medium">
                                    +{{ formatNumber(mov.entrada) }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm">
                                <span v-if="mov.salida > 0" class="text-red-600 font-medium">
                                    -{{ formatNumber(mov.salida) }}
                                </span>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-mono text-gray-600">
                                <template v-if="mov.precio_unitario > 0">
                                    {{ formatNumber(mov.precio_unitario) }}
                                </template>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-mono">
                                <template v-if="mov.total_documento > 0">
                                    {{ formatNumber(mov.total_documento) }}
                                    <span v-if="mov.moneda" class="text-xs text-gray-400 ml-0.5">{{ mov.moneda }}</span>
                                </template>
                                <span v-else class="text-gray-300">—</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap text-sm font-bold">
                                {{ formatNumber(mov.saldo) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
