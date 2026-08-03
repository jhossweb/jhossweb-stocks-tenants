<script setup lang="ts">
import { AlertTriangle, ChevronRight, Package } from 'lucide-vue-next';
import { computed, useAttrs } from 'vue';
import { Card, CardContent } from '@/components/ui/card';
import type { ProductKpis, ProductMovement } from '@/types/Dashboard';

const props = defineProps<{
    products: ProductKpis;
}>();

const attrs = useAttrs();

const clickable = computed(() => Boolean(attrs.onClick));

const cardClass = computed(() => [
    'h-full',
    clickable.value
        ? 'group cursor-pointer transition-[box-shadow,transform] hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
        : '',
]);

const lowStockClass = computed(() => {
    if (props.products.lowStock.length > 0) {
        return 'text-amber-500 dark:text-amber-400';
    }

    return 'text-foreground';
});

const lowStockText = computed(() => {
    const count = props.products.lowStock.length;

    if (count === 0) {
        return 'Sin alertas';
    }

    return `${count} producto${count !== 1 ? 's' : ''} bajo el mínimo`;
});

function formatCount(value: number): string {
    return new Intl.NumberFormat('es', {
        maximumFractionDigits: 0,
    }).format(value);
}

function topProductName(items: ProductMovement[]): string {
    if (items.length === 0) {
        return 'Sin ventas';
    }

    return items[0].pro_name;
}

function topProductQty(items: ProductMovement[]): string {
    if (items.length === 0) {
        return '';
    }

    return `${formatCount(items[0].quantity)} unds`;
}

function handleActivation() {
    if (!clickable.value) {
        return;
    }

    const handler = attrs.onClick;

    if (Array.isArray(handler)) {
        handler.forEach((listener) => listener?.());
    } else if (typeof handler === 'function') {
        handler();
    }
}

function onKeydown(event: KeyboardEvent) {
    if (!clickable.value) {
        return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleActivation();
    }
}
</script>

<template>
    <Card
        :role="clickable ? 'button' : undefined"
        :tabindex="clickable ? 0 : undefined"
        :aria-label="clickable ? 'Ver detalle de productos' : undefined"
        :class="cardClass"
        @keydown="onKeydown"
    >
        <CardContent class="flex h-full flex-col gap-4">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-9 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                    >
                        <Package class="size-4" aria-hidden="true" />
                    </div>
                    <span
                        class="text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Productos
                    </span>
                </div>
                <span
                    v-if="clickable"
                    class="flex items-center gap-1 text-xs font-medium text-muted-foreground"
                >
                    Ver detalle
                    <ChevronRight
                        class="size-3.5 transition-transform group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Total SKUs
                    </span>
                    <span
                        class="text-xl font-semibold text-foreground tabular-nums"
                    >
                        {{ formatCount(products.totalSkus) }}
                    </span>
                </div>

                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Stock bajo
                    </span>
                    <span
                        class="flex items-center gap-1.5 text-xl font-semibold tabular-nums"
                        :class="lowStockClass"
                    >
                        <AlertTriangle
                            v-if="products.lowStock.length > 0"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ products.lowStock.length }}
                    </span>
                    <span class="text-xs text-muted-foreground">{{
                        lowStockText
                    }}</span>
                </div>

                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Más vendido
                    </span>
                    <span
                        class="truncate text-sm font-semibold text-foreground"
                        :title="topProductName(products.topSelling)"
                    >
                        {{ topProductName(products.topSelling) }}
                    </span>
                    <span class="text-xs text-muted-foreground">{{
                        topProductQty(products.topSelling)
                    }}</span>
                </div>

                <div class="flex min-w-0 flex-col gap-1">
                    <span
                        class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Menos vendido
                    </span>
                    <span
                        class="truncate text-sm font-semibold text-foreground"
                        :title="topProductName(products.worstSelling)"
                    >
                        {{ topProductName(products.worstSelling) }}
                    </span>
                    <span class="text-xs text-muted-foreground">{{
                        topProductQty(products.worstSelling)
                    }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
