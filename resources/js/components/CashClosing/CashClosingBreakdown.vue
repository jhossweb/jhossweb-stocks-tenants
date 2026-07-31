<script setup lang="ts">
import { computed } from 'vue';
import {
    currencyLabels,
    formatCoinAmount,
    methodLabels,
} from '@/composables/Finance/useCoinFormat';
import type { ReceiptDetail } from '@/types/Finance/CashClosing';

const props = defineProps<{
    details: ReceiptDetail[];
}>();

const grouped = computed(() => {
    const groups: Record<string, Record<string, ReceiptDetail[]>> = {};

    for (const detail of props.details) {
        if (!groups[detail.coin]) {
            groups[detail.coin] = {};
        }

        if (!groups[detail.coin][detail.method]) {
            groups[detail.coin][detail.method] = [];
        }

        groups[detail.coin][detail.method].push(detail);
    }

    return groups;
});

function formatDate(dateStr: string): string {
    if (!dateStr) {
        return '';
    }

    const [y, m, d] = dateStr.split('-');

    return `${d}-${m}-${y}`;
}
</script>

<template>
    <div v-if="details.length > 0" class="overflow-x-auto">
        <table class="w-full text-sm">
            <tbody>
                <template v-for="(methods, coin) in grouped" :key="coin">
                    <tr class="bg-muted/20">
                        <td
                            class="px-4 py-3 text-base font-bold text-zinc-100"
                            colspan="6"
                        >
                            {{ currencyLabels[coin] ?? coin }}
                        </td>
                    </tr>

                    <template v-for="(items, method) in methods" :key="method">
                        <tr class="bg-muted/10">
                            <td
                                class="px-6 py-2 text-xs font-semibold text-zinc-300"
                                colspan="6"
                            >
                                {{ methodLabels[method] ?? method }}
                            </td>
                        </tr>

                        <tr class="border-b border-zinc-800/60">
                            <th
                                class="px-6 py-2 text-left text-xs font-medium text-zinc-500"
                            >
                                Comprobante
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium text-zinc-500"
                            >
                                Fecha
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium text-zinc-500"
                            >
                                Nro. Documento
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium text-zinc-500"
                            >
                                Cliente
                            </th>
                            <th
                                class="px-3 py-2 text-left text-xs font-medium text-zinc-500"
                            >
                                Banco
                            </th>
                            <th
                                class="px-3 py-2 text-right text-xs font-medium text-zinc-500"
                            >
                                Monto
                            </th>
                        </tr>

                        <tr
                            v-for="(item, i) in items"
                            :key="i"
                            class="border-b border-zinc-800/30 hover:bg-zinc-800/10"
                        >
                            <td class="px-6 py-3 text-sm text-zinc-300">
                                {{ item.reference || '-' }}
                            </td>
                            <td class="px-3 py-3 text-sm text-zinc-400">
                                {{ formatDate(item.payment_date) }}
                            </td>
                            <td class="px-3 py-3 text-sm text-zinc-300">
                                {{ item.document }}
                            </td>
                            <td class="px-3 py-3 text-sm text-zinc-300">
                                {{ item.customer }}
                            </td>
                            <td class="px-3 py-3 text-sm text-zinc-400">
                                {{ item.bank || '-' }}
                            </td>
                            <td
                                class="px-3 py-3 text-right font-mono text-base font-semibold text-green-400 tabular-nums"
                            >
                                {{ formatCoinAmount(item.amount, item.coin) }}
                            </td>
                        </tr>
                    </template>
                </template>
            </tbody>
        </table>
    </div>
</template>
