<script setup lang="ts">
import { computed } from 'vue';
import { formatAmount } from '@/composables/Finance/useCoinFormat';

const props = defineProps<{
    openingBalance: number;
    totalSales: number;
    totalReceived: number;
    inflows: number;
    outflows: number;
}>();

const rows = computed(() => [
    {
        label: 'Saldo Inicial (USD)',
        value: props.openingBalance,
        tone: 'plain' as const,
    },
    {
        label: '+ Ventas de contado',
        value: props.totalSales,
        tone: 'in' as const,
    },
    {
        label: '+ Cobros / Abonos recibidos',
        value: props.totalReceived,
        tone: 'in' as const,
    },
    { label: '+ Ingresos manuales', value: props.inflows, tone: 'in' as const },
    {
        label: '- Egresos manuales',
        value: props.outflows,
        tone: 'out' as const,
    },
]);
</script>

<template>
    <ul class="divide-y divide-zinc-800">
        <li
            v-for="row in rows"
            :key="row.label"
            class="flex items-center justify-between gap-4 py-3"
        >
            <span class="text-sm text-zinc-300">{{ row.label }}</span>
            <span
                class="font-mono text-base font-semibold tabular-nums"
                :class="{
                    'text-green-400': row.tone === 'in',
                    'text-red-400': row.tone === 'out',
                    'text-zinc-100': row.tone === 'plain',
                }"
            >
                {{ row.tone === 'out' ? '- ' : row.tone === 'in' ? '+ ' : ''
                }}{{ formatAmount(row.value) }}
            </span>
        </li>
    </ul>
</template>
