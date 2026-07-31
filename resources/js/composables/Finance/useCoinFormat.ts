export const currencyLabels: Record<string, string> = {
    USD: 'USD',
    BS: 'BS / VES',
    COP: 'COP',
    EUR: 'EUR',
};

export const methodLabels: Record<string, string> = {
    EFECTIVO: 'Efectivo',
    TRANSFERENCIA: 'Transferencias',
    PAGO_MOVIL: 'Pago Móvil',
    OTRO: 'Otros',
};

const currencySymbols: Record<string, string> = {
    USD: '$',
    BS: 'Bs ',
    COP: '$ ',
    EUR: '€ ',
};

export function coinDecimals(coin: string): number {
    if (coin === 'COP') {
        return 0;
    }

    if (coin === 'USD') {
        return 4;
    }

    return 2;
}

export function coinStep(coin: string): string {
    if (coin === 'COP') {
        return '1';
    }

    if (coin === 'USD') {
        return '0.0001';
    }

    return '0.01';
}

export function roundToCoinPrecision(coin: string, value: number): number {
    return Number(value.toFixed(coinDecimals(coin)));
}

export function currencySymbol(coin: string): string {
    return currencySymbols[coin] ?? `${coin} `;
}

export function formatAmount(value: number): string {
    return new Intl.NumberFormat('es', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

export function formatCoinAmount(value: number, coin: string): string {
    return currencySymbol(coin) + formatAmount(value);
}

export function formatCoinValue(value: number, coin: string): string {
    const decimals = coinDecimals(coin);

    return (
        currencySymbol(coin) +
        new Intl.NumberFormat('es', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value)
    );
}

export function formatCoinSummary(values: Record<string, number>): string {
    const entries = Object.entries(values);

    if (entries.length === 0) {
        return '—';
    }

    return entries
        .map(([coin, value]) => formatCoinValue(value, coin))
        .join(', ');
}

export function normalizeDifferences(
    differences: Record<string, number> | null | undefined,
): Record<string, number> {
    const normalized: Record<string, number> = {};

    for (const [coin, diff] of Object.entries(differences ?? {})) {
        normalized[coin] = roundToCoinPrecision(coin, diff);
    }

    return normalized;
}
