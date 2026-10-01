import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import type { ExpenseBreakdown, ExpenseCategoryRow } from '@/types/Dashboard';

const typeLabels: Record<string, string> = {
    fijo: 'Fijo',
    variable: 'Variable',
};

/**
 * Vista de la tabla de gastos por categoría.
 *
 * La prop llega diferida, así que mientras no exista el desglose se expone
 * `isLoading` para pintar un esqueleto en vez de una tabla vacía que parece
 * "no hay gastos" cuando en realidad aún no se consultó.
 */
export function useExpenseBreakdown(
    source: MaybeRefOrGetter<ExpenseBreakdown | undefined>,
    isUsdMode: MaybeRefOrGetter<boolean>,
) {
    const breakdown = computed(() => toValue(source) ?? null);

    const isLoading = computed(() => breakdown.value === null);

    /**
     * Monto de la fila. En modo USD muestra el equivalente convertido; sin tasa
     * cae al desglose por moneda, que es el único dato real disponible.
     */
    function rowAmount(category: ExpenseCategoryRow): string {
        if (toValue(isUsdMode) && category.usd !== null) {
            return `$ ${category.usd.toFixed(2)}`;
        }

        const entries = Object.entries(category.amounts ?? {})
            .filter(([, value]) => Number(value) !== 0)
            .map(([coin, value]) => formatCoinValue(Number(value), coin));

        return entries.length > 0 ? entries.join(', ') : '—';
    }

    function typeLabel(type: string): string {
        return typeLabels[type] ?? type;
    }

    return { breakdown, isLoading, rowAmount, typeLabel };
}
