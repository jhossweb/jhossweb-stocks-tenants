interface LatestRate {
    rate_usd: number;
    rate_cop: number;
    rate_eur: number;
}

export function usePayment() {
    function resolveRate(coin: string, latestRate: LatestRate | null): number {
        if (!latestRate) {
            return 1;
        }

        switch (coin) {
            case 'USD':
                return 1;
            case 'COP':
                return Number(latestRate.rate_cop) || 1;
            case 'EUR':
                return Number(latestRate.rate_eur) || 1;
            default:
                return Number(latestRate.rate_usd) || 1;
        }
    }

    function resolveRateForStorage(
        coin: string,
        latestRate: LatestRate | null,
    ): number {
        if (!latestRate) {
            return 1;
        }

        switch (coin) {
            case 'USD':
            case 'BS':
            case 'VES':
                return Number(latestRate.rate_usd) || 1;
            case 'COP':
                return Number(latestRate.rate_cop) || 1;
            case 'EUR':
                return Number(latestRate.rate_eur) || 1;
            default:
                return Number(latestRate.rate_usd) || 1;
        }
    }

    function convertAmount(amount: number, rate: number): number {
        return amount / rate;
    }

    return { resolveRate, resolveRateForStorage, convertAmount };
}
