import { computed, ref, watch } from 'vue';
import type { Ref } from 'vue';
import { roundToCoinPrecision } from '@/composables/Finance/useCoinFormat';
import type {
    CashClosingPreview,
    CountedDetail,
} from '@/types/Finance/CashClosing';

export function useCashCount(preview: Ref<CashClosingPreview | undefined>) {
    const countedRows = ref<CountedDetail[]>([]);

    watch(
        () => preview.value?.grouped_receipts,
        (grouped) => {
            if (!grouped) {
                countedRows.value = [];

                return;
            }

            const rows: CountedDetail[] = [];

            for (const [coin, methods] of Object.entries(grouped)) {
                for (const method of Object.keys(methods)) {
                    rows.push({ coin, method, counted: 0 });
                }
            }

            countedRows.value = rows;
        },
        { immediate: true },
    );

    const totalsByCoin = computed(() => {
        const totals: Record<string, number> = {};

        for (const row of countedRows.value) {
            totals[row.coin] = (totals[row.coin] ?? 0) + row.counted;
        }

        return totals;
    });

    const expectedByCoin = computed(() => {
        const totals: Record<string, number> = {};

        if (!preview.value) {
            return totals;
        }

        for (const [coin, methods] of Object.entries(
            preview.value.grouped_receipts,
        )) {
            let sum = 0;

            for (const total of Object.values(methods)) {
                sum += total;
            }

            totals[coin] = sum;
        }

        return totals;
    });

    const differencesByCoin = computed(() => {
        const diff: Record<string, number> = {};
        const coins = new Set([
            ...Object.keys(totalsByCoin.value),
            ...Object.keys(expectedByCoin.value),
        ]);

        for (const coin of coins) {
            const counted = totalsByCoin.value[coin] ?? 0;
            const expected = expectedByCoin.value[coin] ?? 0;
            diff[coin] = roundToCoinPrecision(coin, counted - expected);
        }

        return diff;
    });

    const hasDifferences = computed(() => {
        return Object.values(differencesByCoin.value).some(
            (diff) => diff !== 0,
        );
    });

    function resetCount() {
        countedRows.value = [];
    }

    function buildCountPayload() {
        return {
            cc_counted_details: countedRows.value,
            cc_differences: differencesByCoin.value,
        };
    }

    return {
        countedRows,
        totalsByCoin,
        expectedByCoin,
        differencesByCoin,
        hasDifferences,
        resetCount,
        buildCountPayload,
    };
}
