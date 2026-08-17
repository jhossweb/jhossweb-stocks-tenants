export interface ConversionRates {
    rate_usd?: number;
    rate_cop?: number;
    rate_eur?: number;
    rate_cop_bs?: number;
}

export function useCurrencyConversion() {
    function convertCurrency(
        balance: number,
        fromCurrency: string,
        toCurrency: string,
        rates: ConversionRates,
    ): number {
        if (!balance || fromCurrency === toCurrency) {
            return balance;
        }

        const usd = Number(rates.rate_usd) || 1;
        const cop = Number(rates.rate_cop) || 1;
        const eur = Number(rates.rate_eur) || 1;
        const copBs = Number(rates.rate_cop_bs) || 0;

        const from = fromCurrency.toUpperCase();
        const to = toCurrency.toUpperCase();

        if (from === 'USD') {
            if (to === 'COP') {
                return balance * cop;
            }

            if (to === 'BS' || to === 'VES') {
                return balance * usd;
            }

            if (to === 'EUR') {
                return balance * eur;
            }
        }

        if (from === 'COP') {
            if (to === 'USD') {
                return balance / cop;
            }

            if (to === 'BS' || to === 'VES') {
                if (copBs > 0) {
                    return balance / copBs;
                }

                return (balance * usd) / cop;
            }

            if (to === 'EUR') {
                return (balance / cop) * eur;
            }
        }

        if (from === 'BS' || from === 'VES') {
            if (to === 'USD') {
                return balance / usd;
            }

            if (to === 'COP') {
                if (copBs > 0) {
                    return balance * copBs;
                }

                return (balance * cop) / usd;
            }

            if (to === 'EUR') {
                return (balance / usd) * eur;
            }
        }

        if (from === 'EUR') {
            if (to === 'USD') {
                return balance / eur;
            }

            if (to === 'COP') {
                return (balance / eur) * cop;
            }

            if (to === 'BS' || to === 'VES') {
                return (balance / eur) * usd;
            }
        }

        return balance;
    }

    return { convertCurrency };
}
