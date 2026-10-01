<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    FileDown,
    ArrowLeft,
    CalendarDays,
    Warehouse,
    AlertCircle,
    Hash,
    Ruler,
    Loader2,
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
    1: 'Enero',
    2: 'Febrero',
    3: 'Marzo',
    4: 'Abril',
    5: 'Mayo',
    6: 'Junio',
    7: 'Julio',
    8: 'Agosto',
    9: 'Septiembre',
    10: 'Octubre',
    11: 'Noviembre',
    12: 'Diciembre',
};

const undLabels: Record<string, string> = {
    und: 'Und',
    mts: 'Mts',
    kg: 'Kg',
    m2: 'm²',
};

const totalEntradas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.entrada, 0),
);

const totalSalidas = computed(() =>
    movements.value.reduce((sum, m) => sum + m.salida, 0),
);

const stockActual = computed(() => {
    if (!selectedWarehouse.value) {
        return 0;
    }

    const stock = props.currentStocks.find(
        (s) => s.stk_war_id === Number(selectedWarehouse.value),
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
            `${apiBase}/months?warehouse_id=${selectedWarehouse.value}&year=${val}`,
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
    if (
        !selectedWarehouse.value ||
        !selectedYear.value ||
        !selectedMonth.value
    ) {
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
            `${apiBase}/movements?warehouse_id=${selectedWarehouse.value}&year=${selectedYear.value}&month=${selectedMonth.value}`,
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

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/products"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Kardex</h1>
                        <p class="text-xs text-zinc-500">
                            Historial de movimientos del producto
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-2">
                        <h2 class="text-xl font-bold text-zinc-100">
                            {{ product.pro_name }}
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-zinc-800 px-2.5 py-1 font-mono text-xs text-zinc-400"
                            >
                                <Hash class="h-3.5 w-3.5" />
                                {{ product.pro_code }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-md bg-zinc-800 px-2.5 py-1 text-xs text-zinc-400"
                            >
                                <Ruler class="h-3.5 w-3.5" />
                                {{
                                    undLabels[product.pro_und] ||
                                    product.pro_und
                                }}
                            </span>
                            <span
                                v-if="product.pro_conversion_factor > 1"
                                class="inline-flex items-center gap-1 rounded-md bg-amber-900/30 px-2.5 py-1 text-xs text-amber-400"
                            >
                                Factor: ×{{ product.pro_conversion_factor }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-sm"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-zinc-400"
                        >
                            <Warehouse class="h-4 w-4" />
                            Almacén
                        </label>
                        <Select v-model="selectedWarehouse">
                            <SelectTrigger
                                class="border-zinc-700 bg-zinc-800 text-zinc-100"
                            >
                                <SelectValue placeholder="Selecciona almacén" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="w in warehouses"
                                    :key="w.id"
                                    :value="String(w.id)"
                                >
                                    {{ w.war_name }}
                                    <span
                                        v-if="product.pro_type !== 'compuesto'"
                                        class="ml-2 text-xs text-zinc-500"
                                    >
                                        (Stock:
                                        {{
                                            formatNumber(
                                                currentStocks.find(
                                                    (s) =>
                                                        s.stk_war_id === w.id,
                                                )?.stk_cant ?? 0,
                                            )
                                        }})
                                    </span>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-zinc-400"
                        >
                            <CalendarDays class="h-4 w-4" />
                            Año
                        </label>
                        <Select
                            v-model="selectedYear"
                            :disabled="!selectedWarehouse || loadingYears"
                        >
                            <SelectTrigger
                                class="border-zinc-700 bg-zinc-800 text-zinc-100"
                            >
                                <SelectValue
                                    :placeholder="
                                        loadingYears
                                            ? 'Cargando...'
                                            : 'Selecciona año'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="y in years"
                                    :key="y"
                                    :value="String(y)"
                                >
                                    {{ y }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-2 text-sm font-medium text-zinc-400"
                        >
                            <CalendarDays class="h-4 w-4" />
                            Mes
                        </label>
                        <Select
                            v-model="selectedMonth"
                            :disabled="!selectedYear || loadingMonths"
                        >
                            <SelectTrigger
                                class="border-zinc-700 bg-zinc-800 text-zinc-100"
                            >
                                <SelectValue
                                    :placeholder="
                                        loadingMonths
                                            ? 'Cargando...'
                                            : 'Selecciona mes'
                                    "
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="m in months"
                                    :key="m"
                                    :value="String(m)"
                                >
                                    {{ monthNames[m] || m }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    v-if="loaded"
                    class="mt-4 flex justify-end border-t border-zinc-800 pt-4"
                >
                    <a
                        :href="pdfUrl()"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                    >
                        <FileDown class="h-4 w-4" />
                        Exportar PDF
                    </a>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div
                    v-if="loaded"
                    class="grid grid-cols-2 gap-px bg-zinc-800 md:grid-cols-4"
                >
                    <div class="bg-zinc-900 p-4 text-center">
                        <p
                            class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"
                        >
                            Saldo Inicial
                        </p>
                        <p class="mt-1 text-lg font-bold text-zinc-100">
                            {{ formatNumber(initialBalance) }}
                            <span class="text-xs font-normal text-zinc-500">{{
                                undLabels[product.pro_und]
                            }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p
                            class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"
                        >
                            Total Entradas
                        </p>
                        <p class="mt-1 text-lg font-bold text-green-400">
                            +{{ formatNumber(totalEntradas) }}
                            <span class="text-xs font-normal text-zinc-500">{{
                                undLabels[product.pro_und]
                            }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p
                            class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"
                        >
                            Total Salidas
                        </p>
                        <p class="mt-1 text-lg font-bold text-red-400">
                            -{{ formatNumber(totalSalidas) }}
                            <span class="text-xs font-normal text-zinc-500">{{
                                undLabels[product.pro_und]
                            }}</span>
                        </p>
                    </div>
                    <div class="bg-zinc-900 p-4 text-center">
                        <p
                            class="text-[10px] font-semibold tracking-wide text-zinc-500 uppercase"
                        >
                            Stock Actual
                        </p>
                        <p class="mt-1 text-lg font-bold text-zinc-100">
                            {{ formatNumber(stockActual) }}
                            <span class="text-xs font-normal text-zinc-500">{{
                                undLabels[product.pro_und]
                            }}</span>
                        </p>
                    </div>
                </div>

                <div
                    v-if="loadingMovements"
                    class="flex items-center justify-center py-20"
                >
                    <Loader2 class="h-8 w-8 animate-spin text-indigo-400" />
                    <span class="ml-3 text-zinc-500"
                        >Cargando movimientos...</span
                    >
                </div>

                <div
                    v-if="!selectedWarehouse && !loadingMovements"
                    class="flex flex-col items-center justify-center py-20 text-zinc-500"
                >
                    <Warehouse class="mb-3 h-12 w-12 opacity-30" />
                    <p class="text-sm">
                        Selecciona un almacén, año y mes para ver los
                        movimientos
                    </p>
                </div>

                <div
                    v-if="loaded && movements.length === 0 && !loadingMovements"
                    class="flex flex-col items-center justify-center py-20 text-zinc-500"
                >
                    <AlertCircle class="mb-3 h-12 w-12 opacity-30" />
                    <p class="text-sm">No hay movimientos en este período</p>
                </div>

                <div
                    v-if="loaded && movements.length > 0 && !loadingMovements"
                    class="overflow-x-auto"
                >
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Fecha</th>
                                <th class="px-4 py-4 text-left">Documento</th>
                                <th class="px-4 py-4 text-left">Tipo</th>
                                <th class="px-4 py-4 text-left">Contraparte</th>
                                <th class="px-4 py-4 text-right">Entrada</th>
                                <th class="px-4 py-4 text-right">Salida</th>
                                <th class="px-4 py-4 text-right">
                                    Precio Unit.
                                </th>
                                <th class="px-4 py-4 text-right">Total Doc.</th>
                                <th class="px-4 py-4 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr class="bg-zinc-800/20 font-medium">
                                <td
                                    class="px-4 py-4 text-xs text-zinc-500"
                                    colspan="8"
                                >
                                    Saldo inicial
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm font-bold text-zinc-100"
                                >
                                    {{ formatNumber(initialBalance) }}
                                </td>
                            </tr>

                            <tr
                                v-for="(mov, index) in movements"
                                :key="index"
                                class="transition-colors hover:bg-zinc-800/30"
                                :class="{
                                    'bg-blue-900/5': mov.origen === 'compra',
                                    'bg-red-900/5': mov.origen === 'venta',
                                    'bg-amber-900/5': mov.origen === 'ajuste',
                                }"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-300"
                                >
                                    {{ formatDate(mov.fecha) }}
                                </td>
                                <td
                                    class="max-w-[140px] truncate px-4 py-4 font-mono text-xs whitespace-nowrap text-zinc-300"
                                    :title="mov.documento"
                                >
                                    {{ mov.documento }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="{
                                            'bg-blue-900/30 text-blue-400':
                                                mov.origen === 'compra',
                                            'bg-red-900/30 text-red-400':
                                                mov.origen === 'venta',
                                            'bg-amber-900/30 text-amber-400':
                                                mov.origen === 'ajuste',
                                            'bg-zinc-800 text-zinc-400': ![
                                                'compra',
                                                'venta',
                                                'ajuste',
                                            ].includes(mov.origen),
                                        }"
                                    >
                                        {{ mov.tipo_transaccion || mov.origen }}
                                    </span>
                                </td>
                                <td
                                    class="max-w-[160px] truncate px-4 py-4 text-sm whitespace-nowrap text-zinc-400"
                                    :title="mov.contraparte"
                                >
                                    {{ mov.contraparte }}
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm whitespace-nowrap"
                                >
                                    <span
                                        v-if="mov.entrada > 0"
                                        class="font-medium text-green-400"
                                    >
                                        +{{ formatNumber(mov.entrada) }}
                                    </span>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm whitespace-nowrap"
                                >
                                    <span
                                        v-if="mov.salida > 0"
                                        class="font-medium text-red-400"
                                    >
                                        -{{ formatNumber(mov.salida) }}
                                    </span>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-sm whitespace-nowrap text-zinc-400"
                                >
                                    <template v-if="mov.precio_unitario > 0">
                                        {{ formatNumber(mov.precio_unitario) }}
                                    </template>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-sm whitespace-nowrap text-zinc-300"
                                >
                                    <template v-if="mov.total_documento > 0">
                                        {{ formatNumber(mov.total_documento) }}
                                        <span
                                            v-if="mov.moneda"
                                            class="ml-0.5 text-xs text-zinc-500"
                                            >{{ mov.moneda }}</span
                                        >
                                    </template>
                                    <span v-else class="text-zinc-700">—</span>
                                </td>
                                <td
                                    class="px-4 py-4 text-right text-sm font-bold whitespace-nowrap text-zinc-100"
                                >
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
