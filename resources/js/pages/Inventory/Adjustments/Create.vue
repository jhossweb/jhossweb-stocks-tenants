<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PlusCircle, Trash2, ArrowLeft, Save, Loader2, AlertTriangle, ClipboardList } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

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
    if (!selectedWarehouseId.value) {
return [];
}

    const warId = Number(selectedWarehouseId.value);

    return props.products.filter(p => {
        const stock = Array.isArray(p.stocks) ? p.stocks.find((s: any) => s.war_id === warId) : null;

        return stock && stock.cant > 0;
    });
});

const getStockForProduct = (proCode: string) => {
    if (!selectedWarehouseId.value) {
return 0;
}

    const product = props.products.find(p => p.pro_code === proCode);

    if (!product) {
return 0;
}

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

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm">
            <div class="flex items-center gap-4">
                <Link
                    href="/inventory/adjustments"
                    class="text-muted-foreground hover:text-foreground rounded-full p-2 transition-colors hover:bg-accent"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">Nuevo Ajuste de Inventario</h1>
                    <p class="text-xs text-muted-foreground">Salida de productos por merma, deterioro o vencido</p>
                </div>
            </div>
            <button
                type="button"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-all hover:bg-primary/90 disabled:opacity-50"
                @click="openConfirmModal"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </button>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="rounded-xl border border-destructive/50 bg-destructive/10 p-4">
            <p class="mb-1 text-sm font-bold text-destructive">Hay problemas con el envío:</p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <ClipboardList class="size-4 text-primary" />
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Configuración del Ajuste</span>
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">N° Ajuste</label>
                            <Input type="text" v-model="form.inv_number" :class="{ 'border-destructive': form.errors.inv_number }" />
                            <p v-if="form.errors.inv_number" class="mt-1 text-xs text-destructive">{{ form.errors.inv_number }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Almacén</label>
                            <select v-model.number="selectedWarehouseId" class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                <option :value="0">Seleccione almacén...</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.war_name || w.id }}</option>
                            </select>
                            <p v-if="!selectedWarehouseId && form.items.length > 0" class="mt-1 text-xs text-amber-500">Debe seleccionar un almacén</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Fecha</label>
                            <Input type="date" v-model="form.inv_date" />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-muted-foreground">Notas</label>
                            <textarea v-model="form.inv_notes" rows="3" class="bg-background border-input flex w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2" placeholder="Motivo del ajuste..."></textarea>
                            <p v-if="form.errors.inv_notes" class="mt-1 text-xs text-destructive">{{ form.errors.inv_notes }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-6">
                    <div class="flex items-start gap-3">
                        <AlertTriangle class="mt-0.5 size-5 shrink-0 text-amber-500" />
                        <div class="space-y-1 text-xs text-amber-600 dark:text-amber-400">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Importante</p>
                            <p>Este ajuste descontará del inventario la cantidad indicada. Seleccione la razón correcta para cada producto.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:col-span-8">
                <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-6 py-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Productos a Ajustar</span>
                        <button
                            type="button"
                            :disabled="!selectedWarehouseId"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="addItem"
                        >
                            <PlusCircle class="size-4" /> Agregar Item
                        </button>
                    </div>

                    <div v-if="form.items.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground">
                        <ClipboardList class="size-12 opacity-30" />
                        <p class="text-sm">Seleccione un almacén y agregue productos para ajustar</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/50 text-[10px] font-bold uppercase text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-4 text-left">Producto</th>
                                    <th class="px-4 py-4 text-left">Razón de Ajuste</th>
                                    <th class="w-24 px-4 py-4 text-center">Stock Actual</th>
                                    <th class="w-24 px-4 py-4 text-center">Cantidad</th>
                                    <th class="w-12 px-4 py-4" />
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-muted/30 transition-colors">
                                    <td class="min-w-[250px] p-3">
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
                                            triggerClass="bg-transparent border-none hover:bg-muted/50"
                                            @select="(p) => selectProduct(index, p)"
                                        />
                                        <p v-if="form.errors[`items.${index}.min_pro_code`]" class="text-[10px] text-destructive">{{ form.errors[`items.${index}.min_pro_code`] }}</p>
                                    </td>
                                    <td class="min-w-[180px] p-3">
                                        <select v-model="item.min_reason_id" class="bg-background border-input flex h-10 w-full rounded-lg border px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                            <option value="">Seleccione...</option>
                                            <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.adjr_name }}</option>
                                        </select>
                                        <p v-if="form.errors[`items.${index}.min_reason_id`]" class="text-[10px] text-destructive">{{ form.errors[`items.${index}.min_reason_id`] }}</p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-flex items-center rounded-md bg-muted/30 px-3 py-1.5 font-mono text-sm font-bold text-muted-foreground">
                                            {{ item.min_pro_code ? Number(getStockForProduct(item.min_pro_code)).toFixed(4) : '-' }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <input
                                                type="number"
                                                v-model="item.min_quantity"
                                                step="0.0001"
                                                min="0.0001"
                                                class="w-20 rounded-md border-none bg-muted/30 py-1.5 text-center focus:ring-1 focus:ring-ring"
                                            />
                                            <span v-if="item.min_pro_code" class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold uppercase text-primary">
                                                {{ getUndLabel(getProductUnd(item.min_pro_code)) }}
                                            </span>
                                        </div>
                                        <p v-if="form.errors[`items.${index}.min_quantity`]" class="text-center text-[10px] text-destructive">{{ form.errors[`items.${index}.min_quantity`] }}</p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button type="button" class="p-1 text-muted-foreground transition-colors hover:text-destructive" title="Eliminar fila" @click="removeItem(index)">
                                            <Trash2 class="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showConfirmModal">
            <DialogContent class="sm:max-w-[700px]">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-xl font-bold">
                        <AlertTriangle class="size-5 text-amber-500" />
                        Confirmar Ajuste de Inventario
                    </DialogTitle>
                    <DialogDescription>
                        Revise los productos antes de confirmar. Una vez guardado, el stock se descontará automáticamente.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <div class="mb-4 grid grid-cols-2 gap-4 rounded-lg border bg-muted/30 p-4 text-sm">
                        <div><span class="text-muted-foreground">N° Ajuste:</span> <span class="ml-2 font-bold">{{ form.inv_number }}</span></div>
                        <div><span class="text-muted-foreground">Fecha:</span> <span class="ml-2">{{ form.inv_date }}</span></div>
                        <div v-if="form.inv_notes" class="col-span-2">
                            <span class="text-muted-foreground">Notas:</span> <span class="ml-2">{{ form.inv_notes }}</span>
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50 text-[10px] font-bold uppercase text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2 text-left">Producto</th>
                                <th class="px-3 py-2 text-left">Razón</th>
                                <th class="px-3 py-2 text-center">Stock Actual</th>
                                <th class="px-3 py-2 text-center">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="(item, index) in form.items" :key="index">
                                <td class="px-3 py-2">{{ getProductName(item.min_pro_code) }}</td>
                                <td class="px-3 py-2">{{ getReasonName(item.min_reason_id) }}</td>
                                <td class="px-3 py-2 text-center font-mono">{{ Number(getStockForProduct(item.min_pro_code)).toFixed(4) }}</td>
                                <td class="px-3 py-2 text-center font-mono font-bold text-amber-500">{{ Number(item.min_quantity).toFixed(4) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <DialogFooter class="gap-3">
                    <Button variant="outline" @click="showConfirmModal = false">Cancelar</Button>
                    <Button @click="confirmAndSubmit" :disabled="form.processing" class="bg-primary text-primary-foreground hover:bg-primary/90">
                        <Loader2 v-if="form.processing" class="mr-2 size-4 animate-spin" />
                        <Save v-else class="mr-2 size-4" />
                        {{ form.processing ? 'Guardando...' : 'Confirmar y Guardar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
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
