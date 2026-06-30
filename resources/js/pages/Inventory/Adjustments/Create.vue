<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Loader2, AlertTriangle, ClipboardList } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Inventario', href: '#' },
            { title: 'Transacciones', href: '#' },
            { title: 'Ajuste de Inventario', href: '/inventory/adjustments' },
            { title: 'Nuevo Ajuste', href: '/inventory/adjustments/create' },
        ],
    },
});

const props = defineProps<{
    warehouses: any[];
    products: any[];
    reasons: any[];
    transactionTypes: any[];
}>();

const form = useForm({
    inv_number: '',
    inv_date: new Date().toISOString().split('T')[0],
    inv_notes: '',
    items: [
        { min_pro_code: '', min_war_id: 0, min_quantity: 1, min_reason_id: '' },
    ],
});

const showConfirmModal = ref(false);

const selectedWarehouseId = ref<number>(0);

const filteredProducts = computed(() => {
    if (!selectedWarehouseId.value) return [];
    const warId = Number(selectedWarehouseId.value);
    return props.products.filter(p => {
        const stock = Array.isArray(p.stocks) ? p.stocks.find((s: any) => s.war_id === warId) : null;
        return stock && stock.cant > 0;
    });
});

const getStockForProduct = (proCode: string) => {
    if (!selectedWarehouseId.value) return 0;
    const product = props.products.find(p => p.pro_code === proCode);
    if (!product) return 0;
    const stock = Array.isArray(product.stocks) ? product.stocks.find((s: any) => s.war_id === Number(selectedWarehouseId.value)) : null;
    return stock ? stock.cant : 0;
};

const getProductName = (code: string) => {
    const p = props.products.find(p => p.pro_code === code);
    return p ? p.pro_name : 'Seleccione producto';
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

const getReasonName = (id: number | string) => {
    const r = props.reasons.find(r => r.id == id);
    return r ? r.adjr_name : 'Seleccione';
};

const addItem = () => {
    form.items.push({
        min_pro_code: '',
        min_war_id: Number(selectedWarehouseId.value),
        min_quantity: 1,
        min_reason_id: '',
    });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const selectProduct = (index: number, product: any) => {
    form.items[index].min_pro_code = product.pro_code;
    form.items[index].min_war_id = Number(selectedWarehouseId.value);
};

const confirmAndSubmit = () => {
    showConfirmModal.value = false;
    form.post('/inventory/adjustments', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => console.log('Errores:', errors),
    });
};

const openConfirmModal = () => {
    if (!form.inv_number || !selectedWarehouseId.value) {
        return;
    }
    showConfirmModal.value = true;
};
</script>

<template>
    <Head title="Nuevo Ajuste de Inventario" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 p-4 md:p-8 font-sans">
        <div class="max-w-7xl mx-auto space-y-6">

            <div class="flex items-center justify-between bg-zinc-900 p-6 rounded-xl border border-zinc-800 shadow-xl">
                <div class="flex items-center gap-4">
                    <Link href="/inventory/adjustments" class="p-2 hover:bg-zinc-800 rounded-full transition-colors text-zinc-400 hover:text-white">
                        <ArrowLeft class="w-6 h-6" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">Nuevo Ajuste de Inventario</h1>
                        <p class="text-xs text-zinc-500">Salida de productos por merma, deterioro o vencido</p>
                    </div>
                </div>
                <button
                    @click="openConfirmModal"
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
                            <ClipboardList class="w-4 h-4" /> Configuración del Ajuste
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">N° Ajuste</label>
                            <Input type="text" v-model="form.inv_number" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 sm:text-sm transition-colors" />
                            <p v-if="form.errors.inv_number" class="text-xs text-red-500 mt-1">{{ form.errors.inv_number }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Almacén</label>
                            <select v-model.number="selectedWarehouseId" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600 h-10 px-3">
                                <option :value="0" class="bg-zinc-900">Seleccione almacén...</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id" class="bg-zinc-900">{{ w.war_name || w.id }}</option>
                            </select>
                            <p v-if="!selectedWarehouseId && form.items.length > 0" class="text-xs text-amber-500 mt-1">Debe seleccionar un almacén</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Fecha</label>
                            <Input type="date" v-model="form.inv_date" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-1.5">Notas</label>
                            <textarea v-model="form.inv_notes" rows="3" class="w-full bg-zinc-950 border-zinc-800 text-zinc-200 rounded-lg text-sm focus:ring-indigo-600 focus:border-indigo-600 px-3 py-2" placeholder="Motivo del ajuste..."></textarea>
                            <p v-if="form.errors.inv_notes" class="text-xs text-red-500 mt-1">{{ form.errors.inv_notes }}</p>
                        </div>
                    </div>

                    <div class="bg-amber-900/20 border border-amber-500/30 rounded-xl p-6 shadow-sm">
                        <div class="flex items-start gap-3">
                            <AlertTriangle class="w-5 h-5 text-amber-400 mt-0.5 shrink-0" />
                            <div class="text-xs text-amber-300 space-y-1">
                                <p class="font-bold text-amber-400 uppercase text-[10px] tracking-wider">Importante</p>
                                <p>Este ajuste descontará del inventario la cantidad indicada. Seleccione la razón correcta para cada producto.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-8 bg-zinc-900 rounded-xl border border-zinc-800 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-zinc-800 flex justify-between items-center bg-zinc-900">
                        <span class="text-xs font-bold uppercase text-zinc-500 tracking-wider">Productos a Ajustar</span>
                        <button @click="addItem" :disabled="!selectedWarehouseId" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1.5 px-3 py-1.5 hover:bg-indigo-500/10 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <PlusCircle class="w-4 h-4" /> Agregar Item
                        </button>
                    </div>

                    <div v-if="form.items.length === 0" class="p-12 text-center text-zinc-500">
                        <ClipboardList class="w-12 h-12 mx-auto mb-3 opacity-30" />
                        <p class="text-sm">Seleccione un almacén y agregue productos para ajustar</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-950 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                                <tr>
                                    <th class="px-4 py-4 text-left">Producto</th>
                                    <th class="px-4 py-4 text-left">Razón de Ajuste</th>
                                    <th class="px-4 py-4 text-center w-24">Stock Actual</th>
                                    <th class="px-4 py-4 text-center w-24">Cantidad</th>
                                    <th class="px-4 py-4 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-zinc-800/30 transition-colors">
                                    <td class="p-3 min-w-[250px]">
                                        <SelectorModal
                                            title="Seleccionar Producto"
                                            :items="filteredProducts"
                                            :searchFields="['pro_name', 'pro_code']"
                                            :displayFields="[
                                                { label: 'Código', key: 'pro_code', class: 'font-mono text-xs' },
                                                { label: 'Nombre', key: 'pro_name' },
                                                { label: 'Und', key: 'pro_und', class: 'font-mono text-xs text-center' },
                                                { label: 'Stock', key: 'total_stock', class: 'font-mono text-xs text-right' },
                                            ]"
                                            :triggerLabel="getProductName(item.min_pro_code)"
                                            :selectedValue="item.min_pro_code"
                                            triggerClass="bg-transparent border-none hover:bg-zinc-800/50"
                                            @select="(p) => selectProduct(index, p)"
                                        />
                                        <p v-if="form.errors[`items.${index}.min_pro_code`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.min_pro_code`] }}</p>
                                    </td>
                                    <td class="p-3 min-w-[180px]">
                                        <select v-model="item.min_reason_id" class="w-full bg-zinc-950 border-zinc-800 text-zinc-300 rounded-lg text-sm focus:ring-indigo-600 h-10 px-3">
                                            <option value="" class="bg-zinc-900">Seleccione...</option>
                                            <option v-for="r in reasons" :key="r.id" :value="r.id" class="bg-zinc-900">{{ r.adjr_name }}</option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.min_reason_id`]" class="text-[10px] text-red-500">{{ form.errors[`items.${index}.min_reason_id`] }}</p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-mono font-bold bg-zinc-950 text-zinc-400">
                                            {{ item.min_pro_code ? Number(getStockForProduct(item.min_pro_code)).toFixed(2) : '-' }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-1 justify-center">
                                            <input type="number" v-model="item.min_quantity"
                                                step="0.01" min="0.01"
                                                class="w-20 text-center border-none bg-zinc-950/50 rounded-md py-1.5 focus:ring-1 focus:ring-indigo-600 text-zinc-300" />
                                            <span v-if="item.min_pro_code" class="text-[10px] font-bold uppercase text-indigo-400 bg-indigo-500/10 px-1.5 py-0.5 rounded">
                                                {{ getUndLabel(getProductUnd(item.min_pro_code)) }}
                                            </span>
                                        </div>
                                        <p v-if="form.errors[`items.${index}.min_quantity`]" class="text-[10px] text-red-500 text-center">{{ form.errors[`items.${index}.min_quantity`] }}</p>
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

            <Dialog v-model:open="showConfirmModal">
                <DialogContent class="sm:max-w-[700px] bg-zinc-950 border-zinc-800">
                    <DialogHeader>
                        <DialogTitle class="text-xl font-bold text-zinc-100 flex items-center gap-2">
                            <AlertTriangle class="w-5 h-5 text-amber-400" />
                            Confirmar Ajuste de Inventario
                        </DialogTitle>
                        <DialogDescription class="text-zinc-500">
                            Revise los productos antes de confirmar. Una vez guardado, el stock se descontará automáticamente.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="py-4">
                        <div class="grid grid-cols-2 gap-4 mb-4 text-sm bg-zinc-900 rounded-lg p-4 border border-zinc-800">
                            <div><span class="text-zinc-500">N° Ajuste:</span> <span class="text-zinc-200 font-bold ml-2">{{ form.inv_number }}</span></div>
                            <div><span class="text-zinc-500">Fecha:</span> <span class="text-zinc-200 ml-2">{{ form.inv_date }}</span></div>
                            <div class="col-span-2" v-if="form.inv_notes">
                                <span class="text-zinc-500">Notas:</span> <span class="text-zinc-200 ml-2">{{ form.inv_notes }}</span>
                            </div>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-zinc-900 text-zinc-500 text-[10px] uppercase font-bold border-b border-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-left">Razón</th>
                                    <th class="px-3 py-2 text-center">Stock Actual</th>
                                    <th class="px-3 py-2 text-center">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-800">
                                <tr v-for="(item, index) in form.items" :key="index" class="text-zinc-300">
                                    <td class="px-3 py-2">{{ getProductName(item.min_pro_code) }}</td>
                                    <td class="px-3 py-2">{{ getReasonName(item.min_reason_id) }}</td>
                                    <td class="px-3 py-2 text-center font-mono">{{ Number(getStockForProduct(item.min_pro_code)).toFixed(2) }}</td>
                                    <td class="px-3 py-2 text-center font-mono font-bold text-amber-400">{{ Number(item.min_quantity).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <DialogFooter class="gap-3">
                        <Button variant="outline" @click="showConfirmModal = false" class="border-zinc-700 text-zinc-300 hover:bg-zinc-800">
                            Cancelar
                        </Button>
                        <Button @click="confirmAndSubmit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-500 text-white">
                            <Loader2 v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                            <Save v-else class="w-4 h-4 mr-2" />
                            {{ form.processing ? 'Guardando...' : 'Confirmar y Guardar' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
