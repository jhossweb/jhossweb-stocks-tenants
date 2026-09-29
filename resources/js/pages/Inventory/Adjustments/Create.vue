<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowLeft,
    ClipboardList,
    Loader2,
    PlusCircle,
    Save,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import SelectorModal from '@/components/SelectorModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useInventoryAdjustmentForm } from '@/composables/Inventory/useInventoryAdjustmentForm';
import { formFieldError } from '@/lib/utils';
import { index, store } from '@/routes/inventory/adjustments';
import type {
    AdjustmentProduct,
    AdjustmentReason,
    InventoryAdjustmentItem,
    Warehouse,
} from '@/types/Inventory';

const props = defineProps<{
    warehouses: Warehouse[];
    products: AdjustmentProduct[];
    reasons: AdjustmentReason[];
    transactionTypes: unknown[];
}>();

const form = useForm({
    inv_number: '',
    inv_date: new Date().toISOString().split('T')[0] ?? '',
    inv_notes: '',
    items: [
        {
            min_pro_code: '',
            min_war_id: 0,
            min_quantity: 1,
            min_reason_id: '',
        },
    ] as InventoryAdjustmentItem[],
});

const showConfirmModal = ref(false);

const {
    selectedWarehouseId,
    warehouseId,
    hasReasons,
    hasWarehouses,
    productsWithStock,
    stockForProduct,
    productName,
    unitLabel,
    reasonName,
    addItem,
    removeItem,
    selectProduct,
    formatStock,
} = useInventoryAdjustmentForm(form.items, {
    warehouses: props.warehouses,
    products: props.products,
    reasons: props.reasons,
});

const canConfirm = computed(
    () => Boolean(form.inv_number) && warehouseId.value > 0,
);

const confirmAndSubmit = () => {
    showConfirmModal.value = false;

    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const openConfirmModal = () => {
    if (!canConfirm.value) {
        return;
    }

    showConfirmModal.value = true;
};
</script>

<template>
    <Head title="Nuevo Ajuste de Inventario" />

    <div class="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
        <div
            class="flex items-center justify-between rounded-xl border bg-card px-6 py-4 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <Link
                    :href="index.url()"
                    class="rounded-full p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                >
                    <ArrowLeft class="size-5" />
                </Link>
                <div>
                    <h1 class="text-xl font-bold">
                        Nuevo Ajuste de Inventario
                    </h1>
                    <p class="text-xs text-muted-foreground">
                        Salida de productos por merma, deterioro o vencido
                    </p>
                </div>
            </div>
            <Button
                type="button"
                :disabled="form.processing"
                class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold"
                @click="openConfirmModal"
            >
                <Loader2 v-if="form.processing" class="size-4 animate-spin" />
                <Save v-else class="size-4" />
                {{ form.processing ? 'Guardando...' : 'Guardar' }}
            </Button>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="rounded-xl border border-destructive/50 bg-destructive/10 p-4"
        >
            <p class="mb-1 text-sm font-bold text-destructive">
                Hay problemas con el envío:
            </p>
            <ul class="list-inside list-disc text-xs text-destructive/80">
                <li v-for="(error, key) in form.errors" :key="key">
                    {{ error }}
                </li>
            </ul>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-4">
                <div class="rounded-xl border bg-card shadow-sm">
                    <div class="flex items-center gap-2 border-b px-6 py-4">
                        <ClipboardList class="size-4 text-primary" />
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >Configuración del Ajuste</span
                        >
                    </div>
                    <div class="flex flex-col gap-5 p-6">
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >N° Ajuste</label
                            >
                            <Input
                                type="text"
                                v-model="form.inv_number"
                                :class="{
                                    'border-destructive':
                                        form.errors.inv_number,
                                }"
                            />
                            <p
                                v-if="form.errors.inv_number"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.inv_number }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Almacén</label
                            >
                            <Select v-model="selectedWarehouseId">
                                <SelectTrigger class="h-10 w-full rounded-lg">
                                    <SelectValue
                                        placeholder="Seleccione almacén..."
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="warehouse in warehouses"
                                        :key="warehouse.id"
                                        :value="String(warehouse.id)"
                                    >
                                        {{
                                            warehouse.war_name ??
                                            `#${warehouse.id}`
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="!hasWarehouses"
                                class="mt-1 text-xs text-amber-500"
                            >
                                No hay almacenes registrados. Crea uno antes de
                                generar ajustes.
                            </p>
                            <p
                                v-else-if="!warehouseId"
                                class="mt-1 text-xs text-amber-500"
                            >
                                Debe seleccionar un almacén
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Fecha</label
                            >
                            <Input type="date" v-model="form.inv_date" />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-medium text-muted-foreground"
                                >Notas</label
                            >
                            <textarea
                                v-model="form.inv_notes"
                                rows="3"
                                class="flex w-full rounded-lg border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:outline-none"
                                placeholder="Motivo del ajuste..."
                            ></textarea>
                            <p
                                v-if="form.errors.inv_notes"
                                class="mt-1 text-xs text-destructive"
                            >
                                {{ form.errors.inv_notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-amber-500/20 bg-amber-500/5 p-6"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            class="mt-0.5 size-5 shrink-0 text-amber-500"
                        />
                        <div
                            class="space-y-1 text-xs text-amber-600 dark:text-amber-400"
                        >
                            <p
                                class="text-[10px] font-bold tracking-wider text-amber-500 uppercase"
                            >
                                Importante
                            </p>
                            <p>
                                Este ajuste descontará del inventario la
                                cantidad indicada. Seleccione la razón correcta
                                para cada producto.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 lg:col-span-8">
                <div
                    v-if="!hasReasons"
                    class="rounded-xl border border-amber-500/40 bg-amber-500/10 p-6"
                >
                    <div class="flex items-start gap-3">
                        <AlertTriangle
                            class="mt-0.5 size-5 shrink-0 text-amber-500"
                        />
                        <div class="space-y-1 text-sm">
                            <p
                                class="font-bold text-amber-600 dark:text-amber-400"
                            >
                                No hay razones de ajuste registradas
                            </p>
                            <p
                                class="text-xs text-amber-600/80 dark:text-amber-400/80"
                            >
                                Cada renglón necesita una razón (merma,
                                deterioro, vencido). Ejecutá
                                <code
                                    >php artisan db:seed
                                    --class=AdjustmentReasonSeeder</code
                                >
                                o pedí a un administrador que las cargue.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-xl border bg-card shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b px-6 py-4"
                    >
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                            >Productos a Ajustar</span
                        >
                        <button
                            type="button"
                            :disabled="!warehouseId"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold text-primary transition-colors hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="addItem"
                        >
                            <PlusCircle class="size-4" /> Agregar Item
                        </button>
                    </div>

                    <div
                        v-if="form.items.length === 0"
                        class="flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground"
                    >
                        <ClipboardList class="size-12 opacity-30" />
                        <p class="text-sm">
                            Seleccione un almacén y agregue productos para
                            ajustar
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b bg-muted/50 text-[10px] font-bold text-muted-foreground uppercase"
                            >
                                <tr>
                                    <th class="px-4 py-4 text-left">
                                        Producto
                                    </th>
                                    <th class="px-4 py-4 text-left">
                                        Razón de Ajuste
                                    </th>
                                    <th class="w-24 px-4 py-4 text-center">
                                        Stock Actual
                                    </th>
                                    <th class="w-24 px-4 py-4 text-center">
                                        Cantidad
                                    </th>
                                    <th class="w-12 px-4 py-4" />
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="transition-colors hover:bg-muted/30"
                                >
                                    <td class="min-w-[250px] p-3">
                                        <SelectorModal
                                            title="Seleccionar Producto"
                                            :items="productsWithStock"
                                            :searchFields="[
                                                'pro_name',
                                                'pro_code',
                                            ]"
                                            :displayFields="[
                                                {
                                                    label: 'Código',
                                                    key: 'pro_code',
                                                    class: 'font-mono text-xs',
                                                },
                                                {
                                                    label: 'Nombre',
                                                    key: 'pro_name',
                                                },
                                                {
                                                    label: 'Und',
                                                    key: 'pro_und',
                                                    class: 'font-mono text-xs text-center',
                                                },
                                                {
                                                    label: 'Stock',
                                                    key: 'total_stock',
                                                    class: 'font-mono text-xs text-right',
                                                },
                                            ]"
                                            :trigger-label="
                                                productName(item.min_pro_code)
                                            "
                                            :selected-value="item.min_pro_code"
                                            trigger-class="bg-transparent border-none hover:bg-muted/50"
                                            @select="
                                                (product: AdjustmentProduct) =>
                                                    selectProduct(
                                                        index,
                                                        product,
                                                    )
                                            "
                                        />
                                        <p
                                            v-if="
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_pro_code`,
                                                )
                                            "
                                            class="text-[10px] text-destructive"
                                        >
                                            {{
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_pro_code`,
                                                )
                                            }}
                                        </p>
                                    </td>
                                    <td class="min-w-[180px] p-3">
                                        <Select
                                            v-model="item.min_reason_id"
                                            :disabled="!hasReasons"
                                        >
                                            <SelectTrigger
                                                class="h-10 w-full rounded-lg"
                                            >
                                                <SelectValue
                                                    placeholder="Seleccione razón..."
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="reason in reasons"
                                                    :key="reason.id"
                                                    :value="String(reason.id)"
                                                >
                                                    {{ reason.adjr_name }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p
                                            v-if="
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_reason_id`,
                                                )
                                            "
                                            class="text-[10px] text-destructive"
                                        >
                                            {{
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_reason_id`,
                                                )
                                            }}
                                        </p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span
                                            class="inline-flex items-center rounded-md bg-muted/30 px-3 py-1.5 font-mono text-sm font-bold text-muted-foreground"
                                        >
                                            {{
                                                item.min_pro_code
                                                    ? formatStock(
                                                          stockForProduct(
                                                              item.min_pro_code,
                                                          ),
                                                      )
                                                    : '-'
                                            }}
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <div
                                            class="flex items-center justify-center gap-1"
                                        >
                                            <input
                                                type="number"
                                                v-model="item.min_quantity"
                                                step="0.0001"
                                                min="0.0001"
                                                class="w-20 rounded-md border-none bg-muted/30 py-1.5 text-center focus:ring-1 focus:ring-ring"
                                            />
                                            <span
                                                v-if="item.min_pro_code"
                                                class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary uppercase"
                                            >
                                                {{
                                                    unitLabel(item.min_pro_code)
                                                }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_quantity`,
                                                )
                                            "
                                            class="text-center text-[10px] text-destructive"
                                        >
                                            {{
                                                formFieldError(
                                                    form,
                                                    `items.${index}.min_quantity`,
                                                )
                                            }}
                                        </p>
                                    </td>
                                    <td class="p-3 text-center">
                                        <button
                                            type="button"
                                            class="p-1 text-muted-foreground transition-colors hover:text-destructive"
                                            title="Eliminar fila"
                                            @click="removeItem(index)"
                                        >
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
                    <DialogTitle
                        class="flex items-center gap-2 text-xl font-bold"
                    >
                        <AlertTriangle class="size-5 text-amber-500" />
                        Confirmar Ajuste de Inventario
                    </DialogTitle>
                    <DialogDescription>
                        Revise los productos antes de confirmar. Una vez
                        guardado, el stock se descontará automáticamente.
                    </DialogDescription>
                </DialogHeader>

                <div class="py-4">
                    <div
                        class="mb-4 grid grid-cols-2 gap-4 rounded-lg border bg-muted/30 p-4 text-sm"
                    >
                        <div>
                            <span class="text-muted-foreground"
                                >N° Ajuste:</span
                            >
                            <span class="ml-2 font-bold">{{
                                form.inv_number
                            }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Fecha:</span>
                            <span class="ml-2">{{ form.inv_date }}</span>
                        </div>
                        <div v-if="form.inv_notes" class="col-span-2">
                            <span class="text-muted-foreground">Notas:</span>
                            <span class="ml-2">{{ form.inv_notes }}</span>
                        </div>
                    </div>

                    <table class="w-full text-sm">
                        <thead
                            class="border-b bg-muted/50 text-[10px] font-bold text-muted-foreground uppercase"
                        >
                            <tr>
                                <th class="px-3 py-2 text-left">Producto</th>
                                <th class="px-3 py-2 text-left">Razón</th>
                                <th class="px-3 py-2 text-center">
                                    Stock Actual
                                </th>
                                <th class="px-3 py-2 text-center">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="(item, index) in form.items"
                                :key="index"
                            >
                                <td class="px-3 py-2">
                                    {{ productName(item.min_pro_code) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ reasonName(item.min_reason_id) }}
                                </td>
                                <td class="px-3 py-2 text-center font-mono">
                                    {{
                                        formatStock(
                                            stockForProduct(item.min_pro_code),
                                        )
                                    }}
                                </td>
                                <td
                                    class="px-3 py-2 text-center font-mono font-bold text-amber-500"
                                >
                                    {{ formatStock(item.min_quantity) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <DialogFooter class="gap-3">
                    <Button variant="outline" @click="showConfirmModal = false"
                        >Cancelar</Button
                    >
                    <Button
                        @click="confirmAndSubmit"
                        :disabled="form.processing"
                        class="bg-primary text-primary-foreground hover:bg-primary/90"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="mr-2 size-4 animate-spin"
                        />
                        <Save v-else class="mr-2 size-4" />
                        {{
                            form.processing
                                ? 'Guardando...'
                                : 'Confirmar y Guardar'
                        }}
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
input[type='number'] {
    -moz-appearance: textfield;
}
input[type='date']::-webkit-calendar-picker-indicator {
    filter: invert(1);
}
</style>
