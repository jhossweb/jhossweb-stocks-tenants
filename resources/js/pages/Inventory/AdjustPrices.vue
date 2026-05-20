<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { 
    Calculator,
    Save,
    Search,
    TrendingUp,
    DollarSign,
    Box,
    AlertCircle
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '#' },
            { title: 'Ajuste de Precios', href: '/inventory/adjust-prices' },
        ],
    },
});

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
        minimumFractionDigits: 2,
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                    <Calculator class="h-8 w-8 text-primary" />
                    Ajuste de Precios
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Actualiza la utilidad y precios de venta basados en el último costo registrado.
                </p>
            </div>
            
            <Button 
                @click="submitAdjustments" 
                :disabled="!hasChanges || form.processing"
                class="shadow-md hover:shadow-lg transition-all"
            >
                <Save class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
            </Button>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-gray-900 p-4 rounded-xl border border-sidebar-border shadow-sm">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input 
                    v-model="searchQuery" 
                    placeholder="Buscar producto por código o nombre..." 
                    class="pl-10"
                />
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <TrendingUp class="h-4 w-4 text-green-500" />
                Fórmula: Precio = Costo / (1 - Utilidad%)
            </div>
        </div>

        <div class="rounded-xl border border-sidebar-border bg-white shadow-sm dark:bg-gray-900 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Código</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700">Producto</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Último Costo (USD)</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-center w-[150px]">Utilidad (%)</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-center w-[180px]">Precio Venta (USD)</th>
                            <th scope="col" class="px-6 py-4 border-b dark:border-gray-700 text-right">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="adj in filteredAdjustments" :key="adj.pro_code" class="bg-white border-b dark:bg-gray-900 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs">{{ adj.pro_code }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ adj.pro_name }}</span>
                                    <span class="text-[10px] text-gray-400">Historial: {{ formatCurrency(adj.last_price) }} ({{ formatPercent(adj.last_utility) }})</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(adj.last_cost) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center">
                                    <Input 
                                        type="number" 
                                        v-model.number="adj.new_utility" 
                                        @input="calculatePrice(adj)"
                                        class="w-24 text-center h-8"
                                        step="0.01"
                                    />
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center">
                                    <div class="relative w-32">
                                        <DollarSign class="absolute left-2 top-1/2 -translate-y-1/2 h-3 w-3 text-gray-400" />
                                        <Input 
                                            type="number" 
                                            v-model.number="adj.new_price" 
                                            @input="calculateUtility(adj)"
                                            class="pl-6 h-8 text-center font-bold"
                                            step="0.0001"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Badge 
                                    :variant="adj.new_price > adj.last_price ? 'default' : (adj.new_price < adj.last_price ? 'destructive' : 'outline')"
                                    class="text-[10px]"
                                >
                                    {{ adj.new_price > adj.last_price ? '+' : '' }}{{ formatCurrency(adj.new_price - adj.last_price) }}
                                </Badge>
                            </td>
                        </tr>
                        <tr v-if="filteredAdjustments.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <Box class="h-8 w-8 mb-2 opacity-20" />
                                    <p>No se encontraron productos</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <div v-if="(form.errors as any).error" class="p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
            <AlertCircle class="h-5 w-5 text-red-600 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-red-800">Error al guardar</p>
                <p class="text-sm text-red-700">{{ (form.errors as any).error }}</p>
            </div>
        </div>

    </div>
</template>
