import { computed, toValue } from 'vue';
import type { MaybeRefOrGetter } from 'vue';
import { formatCoinValue } from '@/composables/Finance/useCoinFormat';
import type { CurrencyMap } from '@/types/Dashboard';

export interface DisplayedAmount {
    currency: string;
    value: number;
    /** El monto destacado en la tarjeta. */
    isPrimary: boolean;
}

export interface AmountsDisplay {
    /** Montos a mostrar, ya formateados y en el orden en que se pintan. */
    entries: DisplayedAmount[];
    primary: DisplayedAmount | null;
    /** No hay nada que mostrar para el período. */
    isEmpty: boolean;
    /** Se está pidiendo USD pero no hay tasa: hay que mostrar el desglose. */
    usdUnavailable: boolean;
}

/**
 * Decide qué montos muestra un KPI según la preferencia de visualización.
 *
 * En modo USD se muestra el equivalente convertido (una sola cifra
 * comparable). En modo multi se muestra una entrada por moneda, ordenadas por
 * valor absoluto dentro de su propia moneda.
 *
 * Si se pide USD y no hay tasa cargada, se cae al desglose por moneda en lugar
 * de mostrar un cero: un total en USD sin tasa es un dato que no existe, no un
 * total de cero.
 */
export function useAmountsDisplay(
    amounts: MaybeRefOrGetter<CurrencyMap | undefined>,
    usd: MaybeRefOrGetter<number | null | undefined>,
    isUsdMode: MaybeRefOrGetter<boolean>,
    primaryCurrency: MaybeRefOrGetter<string> = 'USD',
) {
    const entries = computed<DisplayedAmount[]>(() => {
        const map = (toValue(amounts) ?? {}) as Record<string, number>;
        const visible = Object.entries(map)
            .filter(([, value]) => value !== undefined && value !== null)
            .map(([currency, value]) => ({ currency, value: Number(value) }))
            .filter((entry) => entry.value !== 0);

        const usdValue = toValue(usd);
        const wantsUsd = toValue(isUsdMode);

        if (wantsUsd && usdValue !== undefined && usdValue !== null) {
            // El equivalente en USD sustituye al desglose: es la misma magnitud
            // expresada en una moneda comparable, no un dato adicional.
            return [{ currency: 'USD', value: usdValue, isPrimary: true }];
        }

        const preferred = toValue(primaryCurrency);
        const sorted = [...visible].sort((a, b) => b.value - a.value);
        const lead = sorted.find((entry) => entry.currency === preferred);

        return sorted.map((entry) => ({
            ...entry,
            isPrimary: entry === (lead ?? sorted[0]),
        }));
    });

    return {
        entries,
        primary: computed(() => entries.value.find((e) => e.isPrimary) ?? null),
        isEmpty: computed(() => entries.value.length === 0),
        usdUnavailable: computed(
            () =>
                toValue(isUsdMode) &&
                (toValue(usd) === undefined || toValue(usd) === null),
        ),
    };
}

export function formatDisplayAmount(currency: string, value: number): string {
    return formatCoinValue(value, currency);
}
