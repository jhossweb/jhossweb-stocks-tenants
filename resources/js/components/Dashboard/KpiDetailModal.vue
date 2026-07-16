<script setup lang="ts">
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import type { CurrencyMap, TrendData, ApArKpis } from '@/types/Dashboard';

defineProps<{
    open: boolean;
    title: string;
    description?: string;
    amounts: CurrencyMap;
    trend?: TrendData;
    secondaryAmounts?: ApArKpis;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

function formatAmount(value: number | undefined): string {
    if (value === undefined || value === null) {
        return '-';
    }

    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
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

function currencyEntries(map: CurrencyMap): [string, number][] {
    return Object.entries(map)
        .filter(([, v]) => v !== undefined && v !== null && v !== 0);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div v-if="trend" class="flex items-center gap-2">
                    <span class="text-muted-foreground text-sm">Tendencia:</span>
                    <Badge
                        :variant="trend.direction === 'up' ? 'default' : trend.direction === 'down' ? 'destructive' : 'secondary'"
                        class="gap-0.5"
                    >
                        <TrendingUp v-if="trend.direction === 'up'" class="size-3" />
                        <TrendingDown v-else-if="trend.direction === 'down'" class="size-3" />
                        <Minus v-else class="size-3" />
                        {{ trend.direction === 'up' ? '+' : '' }}{{ trend.percentage }}%
                    </Badge>
                </div>

                <div class="rounded-lg border">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium">Moneda</th>
                                <th class="px-4 py-2 text-right font-medium">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="[currency, amount] in currencyEntries(amounts)"
                                :key="currency"
                                class="border-b last:border-0"
                            >
                                <td class="px-4 py-2">{{ currencySymbol(currency) }} {{ currency }}</td>
                                <td class="px-4 py-2 text-right font-medium">
                                    {{ formatAmount(amount) }}
                                </td>
                            </tr>
                            <tr v-if="currencyEntries(amounts).length === 0">
                                <td colspan="2" class="text-muted-foreground px-4 py-4 text-center">
                                    Sin datos para este período
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="secondaryAmounts"
                    class="rounded-lg border"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-2 text-left font-medium">Concepto</th>
                                <th class="px-4 py-2 text-right font-medium">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="secondaryAmounts.balance_usd">
                                <td class="px-4 py-2">Saldo (USD)</td>
                                <td class="px-4 py-2 text-right font-medium">
                                    $ {{ formatAmount(secondaryAmounts.balance_usd.USD) }}
                                </td>
                            </tr>
                            <tr v-if="secondaryAmounts.collected">
                                <td class="px-4 py-2">Cobrado</td>
                                <td class="px-4 py-2 text-right font-medium">
                                    $ {{ formatAmount(secondaryAmounts.collected.USD) }}
                                </td>
                            </tr>
                            <tr v-if="secondaryAmounts.paid">
                                <td class="px-4 py-2">Pagado</td>
                                <td class="px-4 py-2 text-right font-medium">
                                    $ {{ formatAmount(secondaryAmounts.paid.USD) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
