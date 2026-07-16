<script setup lang="ts">
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { CurrencyMap, TrendData } from '@/types/Dashboard';

const props = defineProps<{
    label: string;
    amounts: CurrencyMap;
    trend?: TrendData;
    primaryCurrency?: string;
}>();

const emit = defineEmits<{
    click: [];
}>();

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
}

function sortedCurrencies(): [string, number][] {
    return Object.entries(props.amounts)
        .filter(([, v]) => v !== undefined && v !== null && v !== 0)
        .sort(([, a], [, b]) => b - a);
}

function currencySymbol(currency: string): string {
    const symbols: Record<string, string> = {
        USD: '$',
        COP: '$',
        BS: 'Bs',
        EUR: '€',
    };

    return symbols[currency] ?? currency;
}
</script>

<template>
    <Card
        class="cursor-pointer transition-shadow hover:shadow-md"
        @click="emit('click')"
    >
        <CardContent class="flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-muted-foreground text-sm font-medium">{{ label }}</span>
                <Badge
                    v-if="trend"
                    :variant="trend.direction === 'up' ? 'default' : trend.direction === 'down' ? 'destructive' : 'secondary'"
                    class="gap-0.5"
                >
                    <TrendingUp v-if="trend.direction === 'up'" class="size-3" />
                    <TrendingDown v-else-if="trend.direction === 'down'" class="size-3" />
                    <Minus v-else class="size-3" />
                    {{ trend.percentage }}%
                </Badge>
            </div>

            <div
                v-for="[currency, amount] in sortedCurrencies()"
                :key="currency"
                :class="[
                    'font-semibold',
                    currency === (primaryCurrency ?? 'USD')
                        ? 'text-foreground text-2xl'
                        : 'text-muted-foreground text-sm',
                ]"
            >
                {{ currencySymbol(currency) }} {{ formatAmount(amount) }}
            </div>

            <div
                v-if="sortedCurrencies().length === 0"
                class="text-muted-foreground text-sm"
            >
                Sin datos
            </div>
        </CardContent>
    </Card>
</template>
