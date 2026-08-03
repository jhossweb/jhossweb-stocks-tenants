<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next';
import type { Component } from 'vue';
import { computed, useAttrs } from 'vue';
import TrendBadge from '@/components/Dashboard/TrendBadge.vue';
import { Card, CardContent } from '@/components/ui/card';
import { currencySymbol } from '@/composables/Finance/useCoinFormat';
import type { CurrencyMap, TrendData } from '@/types/Dashboard';

const props = defineProps<{
    label: string;
    amounts: CurrencyMap;
    trend?: TrendData;
    primaryCurrency?: string;
    variant?: 'default' | 'success' | 'danger' | 'info';
    icon?: Component;
}>();

const attrs = useAttrs();

const clickable = computed(() => Boolean(attrs.onClick));

const cardClass = computed(() => [
    'h-full',
    clickable.value
        ? 'group cursor-pointer transition-[box-shadow,transform] hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
        : '',
]);

const tone = computed(() => {
    switch (props.variant) {
        case 'success':
            return {
                value: 'text-emerald-600 dark:text-emerald-400',
                tile: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            };
        case 'danger':
            return {
                value: 'text-red-600 dark:text-red-400',
                tile: 'bg-red-500/10 text-red-600 dark:text-red-400',
            };
        case 'info':
            return {
                value: 'text-sky-600 dark:text-sky-400',
                tile: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
            };
        default:
            return {
                value: 'text-foreground',
                tile: 'bg-muted text-muted-foreground',
            };
    }
});

const currencyEntries = computed<[string, number][]>(() => {
    return Object.entries(props.amounts)
        .filter(
            ([, value]) => value !== undefined && value !== null && value !== 0,
        )
        .sort(([, a], [, b]) => b - a) as [string, number][];
});

const primaryEntry = computed<[string, number] | undefined>(() => {
    const primary = props.primaryCurrency ?? 'USD';
    const found = currencyEntries.value.find(
        ([currency]) => currency === primary,
    );

    if (found) {
        return found;
    }

    return currencyEntries.value[0];
});

const secondaryEntries = computed<[string, number][]>(() => {
    return currencyEntries.value.filter(
        ([currency]) => currency !== primaryEntry.value?.[0],
    );
});

function formatAmount(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 4,
    }).format(value);
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
        :aria-label="clickable ? 'Ver detalle de ' + label : undefined"
        :class="cardClass"
        @keydown="onKeydown"
    >
        <CardContent class="flex h-full flex-col gap-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        v-if="icon"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                        :class="tone.tile"
                    >
                        <component
                            :is="icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </div>
                    <span
                        class="truncate text-sm font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ label }}
                    </span>
                </div>
                <TrendBadge :trend="trend" />
            </div>

            <template v-if="primaryEntry">
                <div
                    class="text-2xl font-semibold tracking-tight tabular-nums"
                    :class="tone.value"
                >
                    {{ currencySymbol(primaryEntry[0])
                    }}{{ formatAmount(primaryEntry[1]) }}
                </div>

                <div
                    v-if="secondaryEntries.length > 0"
                    class="flex flex-wrap gap-x-4 gap-y-1"
                >
                    <span
                        v-for="[currency, amount] in secondaryEntries"
                        :key="currency"
                        class="text-sm text-muted-foreground tabular-nums"
                    >
                        {{ currencySymbol(currency) }}{{ formatAmount(amount) }}
                    </span>
                </div>
            </template>
            <div v-else class="py-2 text-sm text-muted-foreground">
                Sin datos para este período
            </div>

            <div
                v-if="clickable"
                class="mt-auto flex items-center gap-1 pt-1 text-xs font-medium text-muted-foreground"
            >
                Ver detalle
                <ChevronRight
                    class="size-3.5 transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                />
            </div>
        </CardContent>
    </Card>
</template>
