<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowDownRight,
    ArrowUpRight,
    Boxes,
    FileDown,
    FilterX,
    Loader2,
    Search,
    SlidersHorizontal,
    Tags,
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';
import PriceHistoryDrawer from '@/components/Inventory/PriceHistoryDrawer.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    emptyFilters,
    filtersToParams,
    formatDate,
    formatPercent,
    formatUsd,
    priceStatus,
    useProductPrices,
} from '@/composables/Inventory/useProductPrices';
import type { PriceListFilters } from '@/composables/Inventory/useProductPrices';
import type { Category, Brand, ProductPriceRow } from '@/types/Inventory';

interface Paginator<T> {
    data: T[];
    links: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    rows: Paginator<ProductPriceRow>;
    filters: Partial<PriceListFilters>;
    categories: Category[];
    brands: Brand[];
}>();

const filters = reactive<PriceListFilters>({
    ...emptyFilters(),
    q: (props.filters.q as string) ?? '',
    category_id:
        props.filters.category_id != null
            ? String(props.filters.category_id)
            : 'all',
    brand_id:
        props.filters.brand_id != null ? String(props.filters.brand_id) : 'all',
    sin_precio: Boolean(props.filters.sin_precio),
    bajo_costo: Boolean(props.filters.bajo_costo),
    sort: typeof props.filters.sort === 'string' ? props.filters.sort : 'name',
});

const { setSort, resetFilters, hasActiveFilters, isFetching } =
    useProductPrices(filters);

const pdfHref = computed(() => {
    const params = new URLSearchParams(
        Object.fromEntries(
            Object.entries(filtersToParams(filters)).map(([k, v]) => [
                k,
                String(v),
            ]),
        ),
    );
    const qs = params.toString();

    return `/inventory/list-prices/pdf${qs ? `?${qs}` : ''}`;
});

const drawerOpen = ref(false);
const selectedCode = ref<string | null>(null);

const openHistory = (row: ProductPriceRow) => {
    selectedCode.value = row.pro_code;
    drawerOpen.value = true;
};

const marginClass = (margin: number | null): string => {
    if (margin === null) {
        return 'text-zinc-500';
    }

    if (margin <= 0) {
        return 'text-red-400 font-semibold';
    }

    if (margin < 10) {
        return 'text-amber-400';
    }

    return 'text-emerald-400';
};

const sortOptions = [
    { value: 'name', label: 'Nombre' },
    { value: 'precio', label: 'Precio' },
    { value: 'fecha', label: 'Fecha' },
];
</script>

<template>
    <Head title="Lista de Precios" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <!-- Encabezado -->
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
                        <h1 class="text-xl font-bold">Lista de Precios</h1>
                        <p class="text-xs text-zinc-500">
                            Precios vigentes del catálogo con costo y margen
                            real. Haz clic en una fila para ver su histórico.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <Button
                        as-child
                        variant="outline"
                        class="border-zinc-700 bg-zinc-900 hover:bg-zinc-800 hover:text-zinc-100"
                    >
                        <Link href="/inventory/adjust-prices">
                            <Tags class="mr-2 h-4 w-4" /> Ajustar Precios
                        </Link>
                    </Button>
                    <a
                        :href="pdfHref"
                        class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500"
                    >
                        <FileDown class="mr-2 h-4 w-4" /> PDF Cliente
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="relative min-w-[220px] flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-500"
                        />
                        <Input
                            v-model="filters.q"
                            placeholder="Buscar por código o nombre…"
                            class="border-zinc-700 bg-zinc-950 pl-10 text-zinc-100 placeholder:text-zinc-600"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Select v-model="filters.category_id">
                            <SelectTrigger
                                class="w-[160px] border-zinc-700 bg-zinc-950"
                            >
                                <SelectValue placeholder="Categoría" />
                            </SelectTrigger>
                            <SelectContent
                                class="border-zinc-700 bg-zinc-900 text-zinc-100"
                            >
                                <SelectItem value="all"
                                    >Todas las categorías</SelectItem
                                >
                                <SelectItem
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="String(category.id)"
                                >
                                    {{ category.cat_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Select v-model="filters.brand_id">
                            <SelectTrigger
                                class="w-[140px] border-zinc-700 bg-zinc-950"
                            >
                                <SelectValue placeholder="Marca" />
                            </SelectTrigger>
                            <SelectContent
                                class="border-zinc-700 bg-zinc-900 text-zinc-100"
                            >
                                <SelectItem value="all"
                                    >Todas las marcas</SelectItem
                                >
                                <SelectItem
                                    v-for="brand in brands"
                                    :key="brand.id"
                                    :value="String(brand.id)"
                                >
                                    {{ brand.bra_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-700 px-3 py-2 text-xs text-zinc-400 transition-colors hover:bg-zinc-800/50"
                        >
                            <Checkbox
                                :model-value="filters.sin_precio"
                                @update:model-value="
                                    (v) => (filters.sin_precio = v === true)
                                "
                            />
                            Sin precio
                        </Label>

                        <Label
                            class="flex cursor-pointer items-center gap-2 rounded-lg border border-zinc-700 px-3 py-2 text-xs text-zinc-400 transition-colors hover:bg-zinc-800/50"
                        >
                            <Checkbox
                                :model-value="filters.bajo_costo"
                                @update:model-value="
                                    (v) => (filters.bajo_costo = v === true)
                                "
                            />
                            Bajo costo
                        </Label>

                        <button
                            v-if="hasActiveFilters()"
                            @click="resetFilters"
                            class="inline-flex items-center gap-1.5 px-2 py-2 text-xs text-zinc-500 transition-colors hover:text-zinc-300"
                        >
                            <FilterX class="h-3.5 w-3.5" /> Limpiar
                        </button>
                    </div>
                </div>

                <div
                    class="mt-3 flex items-center justify-between border-t border-zinc-800/60 pt-3"
                >
                    <p class="flex items-center gap-2 text-xs text-zinc-500">
                        <Loader2
                            v-if="isFetching"
                            class="h-3 w-3 animate-spin text-indigo-400"
                        />
                        {{ rows.total }} producto{{
                            rows.total === 1 ? '' : 's'
                        }}
                    </p>
                    <div class="flex items-center gap-2 text-xs text-zinc-500">
                        <SlidersHorizontal class="h-3.5 w-3.5" /> Ordenar:
                        <button
                            v-for="option in sortOptions"
                            :key="option.value"
                            @click="setSort(option.value)"
                            class="rounded px-2 py-0.5 transition-colors"
                            :class="
                                filters.sort === option.value
                                    ? 'bg-indigo-600/20 text-indigo-300'
                                    : 'hover:text-zinc-300'
                            "
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div
                class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-900 shadow-sm"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead
                            class="border-b border-zinc-800 bg-zinc-950 text-[10px] font-bold text-zinc-500 uppercase"
                        >
                            <tr>
                                <th class="px-4 py-4 text-left">Código</th>
                                <th class="px-4 py-4 text-left">Producto</th>
                                <th class="px-4 py-4 text-right">
                                    Costo actual
                                </th>
                                <th class="px-4 py-4 text-right">
                                    Precio vigente
                                </th>
                                <th class="px-4 py-4 text-right">
                                    Utilidad reg.
                                </th>
                                <th class="px-4 py-4 text-right">
                                    Margen real
                                </th>
                                <th class="px-4 py-4 text-right">Variación</th>
                                <th class="px-4 py-4 text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="row in rows.data"
                                :key="row.pro_code"
                                class="cursor-pointer transition-colors hover:bg-zinc-800/40"
                                @click="openHistory(row)"
                            >
                                <td
                                    class="px-4 py-3.5 font-mono text-xs whitespace-nowrap text-zinc-400"
                                >
                                    {{ row.pro_code }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="font-medium text-zinc-100">{{
                                        row.pro_name
                                    }}</span>
                                    <span
                                        v-if="row.category || row.brand"
                                        class="block text-[10px] text-zinc-600"
                                    >
                                        {{
                                            [row.category, row.brand]
                                                .filter(Boolean)
                                                .join(' · ')
                                        }}
                                        · {{ row.pro_und }}
                                    </span>
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right font-mono whitespace-nowrap text-zinc-300 tabular-nums"
                                >
                                    {{ formatUsd(row.last_cost) }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right whitespace-nowrap"
                                >
                                    <span
                                        class="font-mono font-semibold text-indigo-300 tabular-nums"
                                        >{{ formatUsd(row.last_price) }}</span
                                    >
                                    <span
                                        class="block text-[10px] text-zinc-600"
                                        >{{
                                            formatDate(row.last_price_date)
                                        }}</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right font-mono whitespace-nowrap text-zinc-400 tabular-nums"
                                >
                                    {{ formatPercent(row.last_utility) }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right font-mono whitespace-nowrap tabular-nums"
                                    :class="marginClass(row.real_margin)"
                                >
                                    {{ formatPercent(row.real_margin) }}
                                </td>
                                <td
                                    class="px-4 py-3.5 text-right whitespace-nowrap"
                                >
                                    <span
                                        v-if="row.variation_pct !== null"
                                        class="inline-flex items-center gap-0.5 font-mono text-xs tabular-nums"
                                        :class="
                                            row.variation_pct >= 0
                                                ? 'text-emerald-400'
                                                : 'text-red-400'
                                        "
                                    >
                                        <ArrowUpRight
                                            v-if="row.variation_pct >= 0"
                                            class="h-3 w-3"
                                        />
                                        <ArrowDownRight
                                            v-else
                                            class="h-3 w-3"
                                        />
                                        {{
                                            formatPercent(
                                                Math.abs(row.variation_pct),
                                            )
                                        }}
                                    </span>
                                    <span v-else class="text-zinc-600">—</span>
                                </td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <Badge
                                        v-if="priceStatus(row) === 'bajo-costo'"
                                        variant="destructive"
                                        class="text-[9px] tracking-wide"
                                    >
                                        BAJO COSTO
                                    </Badge>
                                    <Badge
                                        v-else-if="
                                            priceStatus(row) === 'sin-precio'
                                        "
                                        variant="outline"
                                        class="border-zinc-700 text-[9px] tracking-wide text-zinc-500"
                                    >
                                        SIN PRECIO
                                    </Badge>
                                    <span
                                        v-else
                                        class="ml-1 inline-block h-2 w-2 rounded-full bg-emerald-500/70"
                                        title="Con precio y margen positivo"
                                    />
                                </td>
                            </tr>
                            <tr v-if="rows.data.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <Boxes
                                        class="mx-auto mb-2 h-8 w-8 opacity-30"
                                    />
                                    <p>
                                        No se encontraron productos con los
                                        filtros aplicados.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="rows.links.length > 3"
                    class="flex justify-center border-t border-zinc-800 px-6 py-4"
                >
                    <div class="flex gap-1">
                        <template v-for="(link, key) in rows.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="cursor-not-allowed rounded border border-zinc-800 px-4 py-2 text-sm text-zinc-600"
                                v-html="link.label"
                            />
                            <component
                                :is="link.active ? 'span' : Link"
                                v-else
                                :href="link.active ? undefined : link.url"
                                class="rounded border border-zinc-700 px-4 py-2 text-sm text-zinc-300 transition-colors hover:bg-zinc-800"
                                :class="{
                                    'border-indigo-600 bg-indigo-600 font-bold text-white':
                                        link.active,
                                }"
                                preserve-state
                                preserve-scroll
                            >
                                <span v-html="link.label" />
                            </component>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <PriceHistoryDrawer
            v-model:open="drawerOpen"
            :product-code="selectedCode"
        />
    </div>
</template>
