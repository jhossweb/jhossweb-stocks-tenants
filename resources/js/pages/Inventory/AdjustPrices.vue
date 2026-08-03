<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    Search,
    TrendingUp,
    DollarSign,
    Box,
    AlertCircle
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
    props.products.map(p => ({
        ...p,
        new_utility: p.last_utility || 0,
        new_price: p.last_price || 0
    }))
);

const filteredAdjustments = computed(() => {
    if (!searchQuery.value) {
return localAdjustments.value;
}

    const query = searchQuery.value.toLowerCase();

    return localAdjustments.value.filter(adj =>
        adj.pro_code.toLowerCase().includes(query) ||
        adj.pro_name.toLowerCase().includes(query)
    );
});

const calculatePrice = (adj: ProductAdjustment) => {
    if (adj.new_utility >= 100) {
        adj.new_price = 0;

        return;
    }

    const marginFactor = 1 - (adj.new_utility / 100);

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
    adjustments: [] as any[]
});

const submitAdjustments = () => {
    // Only include items that actually changed
    const changes = localAdjustments.value
        .filter(adj =>
            Math.abs(adj.new_price - adj.last_price) > 0.0001 ||
            Math.abs(adj.new_utility - adj.last_utility) > 0.01
        )
        .map(adj => ({
            pro_code: adj.pro_code,
            utility: adj.new_utility,
            price: adj.new_price
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
        }
    });
};

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 4,
        maximumFractionDigits: 4
    }).format(value);
};

const formatPercent = (value: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'decimal',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value) + '%';
};

const hasChanges = computed(() => {
    return localAdjustments.value.some(adj =>
        Math.abs(adj.new_price - adj.last_price) > 0.0001 ||
        Math.abs(adj.new_utility - adj.last_utility) > 0.01
    );
});

</script>

<template>
    <Head title="Ajuste de Precios" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/inventory" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Ajuste de Precios</h1>
                        <p class="text-xs text-zinc-500">Actualiza la utilidad y precios de venta basados en el último costo registrado.</p>
                    </div>
                </div>
                <button
                    @click="submitAdjustments"
                    :disabled="!hasChanges || form.processing"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-semibold transition-all shadow-lg shadow-indigo-900/20 disabled:opacity-50"
                >
                    <Save class="w-4 h-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </div>

            <div class="bg-zinc-900 border border-zinc-800 p-4 rounded-xl shadow-sm">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-sm">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
                        <Input
                            v-model="searchQuery"
                            placeholder="Buscar producto por código o nombre..."
                            class="pl-10 bg-zinc-800 border-zinc-700 text-zinc-100 placeholder:text-zinc-500"
                        />
                    </div>
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <TrendingUp class="h-4 w-4 text-green-500" />
                        Fórmula: Precio = Costo / (1 - Utilidad%)
                    </div>
                </div>
            </div>

            <div class="bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                            <tr>
                                <th class="px-4 py-4 text-left">Código</th>
                                <th class="px-4 py-4 text-left">Producto</th>
                                <th class="px-4 py-4 text-right">Último Costo (USD)</th>
                                <th class="px-4 py-4 text-center w-[150px]">Utilidad (%)</th>
                                <th class="px-4 py-4 text-center w-[180px]">Precio Venta (USD)</th>
                                <th class="px-4 py-4 text-right">Diferencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800">
                            <tr v-for="adj in filteredAdjustments" :key="adj.pro_code" class="hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-4 font-mono text-xs text-zinc-300">{{ adj.pro_code }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-zinc-100">{{ adj.pro_name }}</span>
                                        <span class="text-[10px] text-zinc-500">Historial: {{ formatCurrency(adj.last_price) }} ({{ formatPercent(adj.last_utility) }})</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right font-mono text-zinc-200">
                                    {{ formatCurrency(adj.last_cost) }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center">
                                        <Input
                                            type="number"
                                            v-model.number="adj.new_utility"
                                            @input="calculatePrice(adj)"
                                            class="w-24 text-center h-8 bg-zinc-800 border-zinc-700 text-zinc-100"
                                            step="0.0001"
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center">
                                        <div class="relative w-32">
                                            <DollarSign class="absolute left-2 top-1/2 -translate-y-1/2 h-3 w-3 text-zinc-500" />
                                            <Input
                                                type="number"
                                                v-model.number="adj.new_price"
                                                @input="calculateUtility(adj)"
                                                class="pl-6 h-8 text-center font-bold bg-zinc-800 border-zinc-700 text-zinc-100"
                                                step="0.0001"
                                            />
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <Badge
                                        :variant="adj.new_price > adj.last_price ? 'default' : (adj.new_price < adj.last_price ? 'destructive' : 'outline')"
                                        class="text-[10px]"
                                    >
                                        {{ adj.new_price > adj.last_price ? '+' : '' }}{{ formatCurrency(adj.new_price - adj.last_price) }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="filteredAdjustments.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                                    <Box class="h-8 w-8 mx-auto mb-2 opacity-20" />
                                    <p>No se encontraron productos</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="(form.errors as any).error" class="p-4 bg-red-900/30 border border-red-800 rounded-xl flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-red-400 mt-0.5 shrink-0" />
                <div>
                    <p class="text-sm font-medium text-red-300">Error al guardar</p>
                    <p class="text-sm text-red-400">{{ (form.errors as any).error }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
