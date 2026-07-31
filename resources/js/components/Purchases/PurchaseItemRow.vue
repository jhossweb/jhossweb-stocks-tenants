<script setup lang="ts">
import { computed } from 'vue';
import FormRowDelete from '@/components/Form/FormRowDelete.vue';
import SelectorModal from '@/components/SelectorModal.vue';
import type { Product, TransactionItem, Warehouse } from '@/types/Transaction';

interface RowCatalog {
    getProductName: (code: string) => string;
    getProductIvaLabel: (code: string) => string;
    getProductUnd: (code: string) => string;
    getProductConversion: (code: string) => number;
    getUndLabel: (und: string) => string;
    getItemTotal: (item: TransactionItem) => string;
}

const props = defineProps<{
    catalog: RowCatalog;
    products: Product[];
    warehouses: Warehouse[];
}>();

const item = defineModel<TransactionItem>({ required: true });

const emit = defineEmits<{
    'select-product': [product: Product];
    'quantity-input': [];
    'cost-input': [];
    remove: [];
}>();

const productUnd = computed(() =>
    props.catalog.getProductUnd(item.value.mov_pro_code),
);

const showsConversion = computed(
    () =>
        Boolean(item.value.mov_pro_code) &&
        ['mts', 'kg', 'm2'].includes(productUnd.value) &&
        props.catalog.getProductConversion(item.value.mov_pro_code) > 1,
);
</script>

<template>
    <tr class="transition-colors hover:bg-muted/30">
        <td class="min-w-[220px] p-3">
            <SelectorModal
                title="Seleccionar Producto"
                :items="products"
                :search-fields="['pro_name', 'pro_code']"
                :display-fields="[
                    {
                        label: 'Código',
                        key: 'pro_code',
                        class: 'font-mono text-xs',
                    },
                    { label: 'Nombre', key: 'pro_name' },
                    {
                        label: 'Und',
                        key: 'pro_und',
                        class: 'font-mono text-xs text-center',
                    },
                ]"
                :trigger-label="catalog.getProductName(item.mov_pro_code)"
                :selected-value="item.mov_pro_code"
                trigger-class="bg-transparent border-none hover:bg-muted/50 w-full"
                @select="(p: Product) => emit('select-product', p)"
            />
        </td>
        <td class="p-3">
            <select
                v-model="item.mov_war_id"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground focus:ring-2 focus:ring-ring focus:outline-none"
            >
                <option value="">Seleccione...</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                    {{ w.war_name || w.id }}
                </option>
            </select>
        </td>
        <td class="p-3 text-center">
            <span
                v-if="item._pending"
                class="inline-block size-5 animate-pulse rounded bg-muted"
            />
            <span
                v-else
                class="inline-block rounded-md bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary"
            >
                {{ catalog.getProductIvaLabel(item.mov_pro_code) }}
            </span>
        </td>
        <td class="p-3">
            <div class="flex items-center justify-center gap-1">
                <input
                    v-model.number="item.mov_quantity"
                    type="number"
                    :step="productUnd === 'und' ? 1 : 0.01"
                    :min="productUnd === 'und' ? 1 : 0.01"
                    class="w-20 rounded-md border border-input bg-background py-1.5 text-center text-sm focus:ring-2 focus:ring-ring focus:outline-none"
                    @input="emit('quantity-input')"
                />
                <span
                    v-if="item.mov_pro_code"
                    class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-bold text-primary uppercase"
                >
                    {{ catalog.getUndLabel(productUnd) }}
                </span>
            </div>
            <div
                v-if="showsConversion"
                class="mt-0.5 text-[10px] text-amber-500"
            >
                ×{{ catalog.getProductConversion(item.mov_pro_code) }}
                {{ catalog.getUndLabel(productUnd) }}
            </div>
        </td>
        <td class="p-3">
            <div class="flex items-center gap-1">
                <input
                    v-model.number="item.mov_unit_cost"
                    type="number"
                    step="0.0001"
                    class="w-full border-none bg-transparent text-right text-sm font-bold focus:ring-0"
                    @input="emit('cost-input')"
                />
                <span
                    v-if="item._pending"
                    class="inline-block size-4 animate-pulse rounded bg-muted"
                />
            </div>
        </td>
        <td class="p-3 text-right">
            <span
                v-if="item._pending"
                class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
            />
            <span v-else class="font-mono text-xs font-medium text-primary">
                {{ item.mov_unit_cost_ref }}
            </span>
        </td>
        <td class="p-3 text-right font-bold text-muted-foreground">
            <span
                v-if="item._pending"
                class="inline-block h-4 w-16 animate-pulse rounded bg-muted"
            />
            <span v-else>{{ catalog.getItemTotal(item) }}</span>
        </td>
        <td class="p-3 text-center">
            <FormRowDelete @remove="emit('remove')" />
        </td>
    </tr>
</template>
