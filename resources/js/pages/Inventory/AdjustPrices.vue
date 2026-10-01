<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Search,
    TrendingUp,
    DollarSign,
    Box,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

import adjustPriceRoutes from '@/routes/inventory/adjust-prices';

interface ProductData {
    pro_code: string;
    pro_name: string;
    last_cost: number;
    last_utility: number;
    last_price: number;
}

interface ProductAdjustment extends ProductData {
    new_utility: number;
    new_price: number;
}

const props = defineProps<{
    products: ProductData[];
}>();

const searchQuery = ref('');

const localAdjustments = ref<ProductAdjustment[]>(
    props.products.map((p) => ({
        ...p,
        new_utility: p.last_utility || 0,
        new_price: p.last_price || 0,
    })),
);

const filteredAdjustments = computed(() => {
    if (!searchQuery.value) {
        return localAdjustments.value;
    }

    const query = searchQuery.value.toLowerCase();

    return localAdjustments.value.filter(
        (adj) =>
            adj.pro_code.toLowerCase().includes(query) ||
            adj.pro_name.toLowerCase().includes(query),
    );
});

const calculatePrice = (adj: ProductAdjustment) => {
    if (adj.new_utility >= 100) {
        adj.new_price = 0;

        return;
    }

    const marginFactor = 1 - adj.new_utility / 100;

    if (marginFactor <= 0) {
        adj.new_price = 0;
    } else {
        // Price = Cost / (1 - Margin/100)
        adj.new_price = adj.last_cost / marginFactor;
    }
};

const calculateUtility = (adj: ProductAdjustment) => {
    if (adj.new_price <= 0 || adj.new_price <= adj.last_cost) {
        adj.new_utility = 0;

        return;
    }

    // Utility = (Price - Cost) / Price * 100
    adj.new_utility = ((adj.new_price - adj.last_cost) / adj.new_price) * 100;
};

const form = useForm({
    adjustments: [] as any[],
});

const submitAdjustments = () => {
    // Only include items that actually changed
    const changes = localAdjustments.value
        .filter(
            (adj) =>
                Math.abs(adj.new_price - adj.last_price) > 0.0001 ||
                Math.abs(adj.new_utility - adj.last_utility) > 0.01,
        )
        .map((adj) => ({
            pro_code: adj.pro_code,
            utility: adj.new_utility,
            price: adj.new_price,
        }));

    if (changes.length === 0) {
        return;
    }

    form.adjustments = changes;
    form.post(adjustPriceRoutes.store.url(), {
        onSuccess: () => {
            // Updated values will be reflected via props
            // Resetting local state to match new props if needed,
            // but Inertia will reload the page/props anyway.
        },
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 4,
        maximumFractionDigits: 4,
    }).format(value);
};

const formatPercent = (value: number) => {
    return (
        new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value) + '%'
    );
};

const hasChanges = computed(() => {
    return localAdjustments.value.some(
        (adj) =>
            Math.abs(adj.new_price - adj.last_price) > 0.0001 ||
            Math.abs(adj.new_utility - adj.last_utility) > 0.01,
    );
});
</script>

<template>
    <Head title="Ajuste de Precios" />

    <div class="min-h-screen bg-zinc-950 p-4 font-sans text-zinc-100 md:p-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div
                class="flex items-center justify-between rounded-xl border border-zinc-800 bg-zinc-900 p-6 shadow-xl"
            >
                <div class="flex items-center gap-4">
                    <Link
                        href="/inventory"
                        class="rounded-full p-2 text-zinc-400 transition-colors hover:bg-zinc-800 hover:text-white"
                    >
                        <ArrowLeft class="h-6 w-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Ajuste de Precios</h1>
                        <p class="text-xs text-zinc-500">
                            Actualiza la utilidad y precios de venta basados en
                            el último costo registrado.
                        </p>
                    </div>
                </div>
                <button
                    @click="submitAdjustments"
                    :disabled="!hasChanges || form.processing"
                    class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white shadow-lg shadow-indigo-900/20 transition-all hover:bg-indigo-500 disabled:opacity-50"
                >
                    <Save class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </div>

            <div
                class="rounded-xl border border-zinc-800 bg-zinc-900 p-4 shadow-sm"
            >
                <div
                    class="flex flex-col items-stretch justify-between gap-4 md:flex-row md:items-center"
                >
                    <div class="relative max-w-sm flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="searchQuery"
                            placeholder="Buscar producto por código o nombre..."
                            class="border-zinc-700 bg-zinc-800 pl-10 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <TrendingUp class="h-4 w-4 text-green-500" />
                        Fórmula: Precio = Costo / (1 - Utilidad%)
                    </div>
                </div>
            </div>

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
                                    Último Costo (USD)
                                </th>
                                <th class="w-[150px] px-4 py-4 text-center">
                                    Utilidad (%)
                                </th>
                                <th class="w-[180px] px-4 py-4 text-center">
                                    Precio Venta (USD)
                                </th>
                                <th class="px-4 py-4 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr
                                v-for="adj in filteredAdjustments"
                                :key="adj.pro_code"
                                class="transition-colors hover:bg-zinc-800/30"
                            >
                                <td
                                    class="px-4 py-4 font-mono text-xs text-zinc-300"
                                >
                                    {{ adj.pro_code }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span
                                            class="font-medium text-zinc-100"
                                            >{{ adj.pro_name }}</span
                                        >
                                        <span class="text-[10px] text-zinc-500"
                                            >Historial:
                                            {{
                                                formatCurrency(adj.last_price)
                                            }}
                                            ({{
                                                formatPercent(adj.last_utility)
                                            }})</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="px-4 py-4 text-right font-mono text-zinc-200"
                                >
                                    {{ formatCurrency(adj.last_cost) }}
                                </td>
                                <td class="px-4 py-4">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <Input
                                            type="number"
                                            v-model.number="adj.new_utility"
                                            @input="calculatePrice(adj)"
                                            class="h-8 w-24 border-zinc-700 bg-zinc-800 text-center text-zinc-100"
                                            step="0.0001"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <div class="relative w-32">
                                            <DollarSign
                                                class="absolute top-1/2 left-2 h-3 w-3 -translate-y-1/2 text-zinc-500"
                                            />
                                            <Input
                                                type="number"
                                                v-model.number="adj.new_price"
                                                @input="calculateUtility(adj)"
                                                class="h-8 border-zinc-700 bg-zinc-800 pl-6 text-center font-bold text-zinc-100"
                                                step="0.0001"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Badge
                                        :variant="
                                            adj.new_price > adj.last_price
                                                ? 'default'
                                                : adj.new_price < adj.last_price
                                                  ? 'destructive'
                                                  : 'outline'
                                        "
                                        class="text-[10px]"
                                    >
                                        {{
                                            adj.new_price > adj.last_price
                                                ? '+'
                                                : ''
                                        }}{{
                                            formatCurrency(
                                                adj.new_price - adj.last_price,
                                            )
                                        }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="filteredAdjustments.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-12 text-center text-zinc-500"
                                >
                                    <Box
                                        class="mx-auto mb-2 h-8 w-8 opacity-20"
                                    />
                                    <p>No se encontraron productos</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="(form.errors as any).error"
                class="flex items-start gap-3 rounded-xl border border-red-800 bg-red-900/30 p-4"
            >
                <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-400" />
                <div>
                    <p class="text-sm font-medium text-red-300">
                        Error al guardar
                    </p>
                    <p class="text-sm text-red-400">
                        {{ (form.errors as any).error }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
