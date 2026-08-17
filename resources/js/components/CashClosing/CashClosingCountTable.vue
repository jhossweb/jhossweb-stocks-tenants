<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import {
    coinDecimals,
    coinStep,
    currencyLabels,
    formatCoinValue,
    methodLabels,
} from '@/composables/Finance/useCoinFormat';
import type {
    CountedDetail,
    GroupedReceipts,
} from '@/types/Finance/CashClosing';

const rows = defineModel<CountedDetail[]>('rows', { required: true });

const props = defineProps<{
    groupedReceipts: GroupedReceipts;
    groupedVueltos: GroupedReceipts;
    expectedByCoin: Record<string, number>;
    differencesByCoin: Record<string, number>;
    readonly?: boolean;
}>();

const coins = computed(() => Object.keys(props.expectedByCoin));

function expectedFor(row: CountedDetail): number {
    const expected = props.groupedReceipts[row.coin]?.[row.method] ?? 0;
    const vuelto = props.groupedVueltos[row.coin]?.[row.method] ?? 0;

    return expected - vuelto;
}

function totalsFor(coin: string): number {
    return rows.value
        .filter((row) => row.coin === coin)
        .reduce((sum, row) => sum + row.counted, 0);
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-muted/40">
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                    >
                        Moneda
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold text-zinc-400"
                    >
                        Método
                    </th>
                    <th
                        class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                    >
                        Esperado
                    </th>
                    <th
                        class="px-4 py-3 text-right text-xs font-semibold text-zinc-400"
                    >
                        Contado
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                <tr
                    v-for="(row, i) in rows"
                    :key="i"
                    class="transition-colors hover:bg-zinc-800/20"
                >
                    <td class="px-4 py-3 font-mono text-sm text-zinc-200">
                        {{ currencyLabels[row.coin] ?? row.coin }}
                    </td>
                    <td class="px-4 py-3 text-sm text-zinc-300">
                        {{ methodLabels[row.method] ?? row.method }}
                    </td>
                    <td
                        class="px-4 py-3 text-right font-mono text-base font-medium text-green-400 tabular-nums"
                    >
                        {{ formatCoinValue(expectedFor(row), row.coin) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <Input
                            v-if="!readonly"
                            v-model.number="row.counted"
                            type="number"
                            :step="coinStep(row.coin)"
                            min="0"
                            class="ml-auto h-10 w-40 text-right font-mono text-base tabular-nums"
                            placeholder="0"
                        />
                        <span
                            v-else
                            class="font-mono text-base font-semibold text-zinc-100 tabular-nums"
                        >
                            {{ formatCoinValue(row.counted, row.coin) }}
                        </span>
                    </td>
                </tr>
            </tbody>
            <tfoot class="border-t-2 border-zinc-700 bg-muted/30">
                <tr v-for="coin in coins" :key="coin">
                    <td
                        class="px-4 py-3 text-base font-bold text-zinc-100"
                        colspan="2"
                    >
                        Total {{ currencyLabels[coin] ?? coin }}
                    </td>
                    <td
                        class="px-4 py-3 text-right font-mono text-base font-bold text-green-400 tabular-nums"
                    >
                        {{ formatCoinValue(expectedByCoin[coin], coin) }}
                    </td>
                    <td
                        class="px-4 py-3 text-right font-mono text-lg font-bold tabular-nums"
                        :class="
                            differencesByCoin[coin] === 0
                                ? 'text-zinc-100'
                                : differencesByCoin[coin] > 0
                                  ? 'text-yellow-400'
                                  : 'text-red-400'
                        "
                    >
                        {{ formatCoinValue(totalsFor(coin), coin) }}
                        <span
                            v-if="differencesByCoin[coin] !== 0"
                            class="ml-2 align-middle text-xs font-semibold"
                            :class="
                                differencesByCoin[coin] > 0
                                    ? 'text-yellow-500'
                                    : 'text-red-500'
                            "
                        >
                            ({{ differencesByCoin[coin] > 0 ? '+' : ''
                            }}{{
                                differencesByCoin[coin].toFixed(
                                    coinDecimals(coin),
                                )
                            }})
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
